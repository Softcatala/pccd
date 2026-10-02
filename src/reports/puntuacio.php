<?php

/**
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 * (c) Víctor Pàmies i Riudor <vpamies@gmail.com>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

use Pdo\Mysql;

/**
 * Keeps SQL collation-based deduplication and groups results by punctuation combination.
 *
 * @param 'MODISME'|'PAREMIOTIPUS' $column
 *
 * @return list<string>
 */
function get_unusual_punctuation_combinations(string $column): array
{
    $select = ['`' . $column . '`'];
    $patterns = ['%?¿%', '%,?%', '%,!', '%!.', '%!…', '%?.', '%?…'];
    foreach ($patterns as $index => $pattern) {
        $select[] = "`{$column}` LIKE '{$pattern}' AS `match_{$index}`";
    }

    // Keep LIKE for collation-equivalent punctuation (e.g. full-width question marks).
    $stmt = db_query(
        'SELECT DISTINCT ' . implode(', ', $select)
        . " FROM `00_PAREMIOTIPUS` WHERE `{$column}` LIKE '%!%' OR `{$column}` LIKE '%?%'"
    );
    $matches = array_fill(0, count($patterns), []);
    while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
        $value = $row[0];
        assert(is_string($value));
        foreach (array_keys($matches) as $index) {
            if ($row[$index + 1] === 1 || $row[$index + 1] === '1') {
                $matches[$index][] = $value;
            }
        }
    }

    return array_merge(...$matches);
}

/**
 * @param 'MODISME'|'PAREMIOTIPUS' $column
 *
 * @return list<string>
 */
function get_single_quote_punctuation_matches(string $column): array
{
    $conditions = [];
    foreach ([' ', '.', ',', ';', ':', '-'] as $character) {
        $conditions[] = "`{$column}` LIKE '%\\'{$character}%'";
    }

    $stmt = db_query(
        "SELECT `{$column}` FROM `00_PAREMIOTIPUS`
        WHERE LENGTH(`{$column}`) - LENGTH(REPLACE(`{$column}`, CHAR(39), '')) = 1
        AND (" . implode(' OR ', $conditions) . ')'
    );
    $matches = [];
    while (($value = $stmt->fetchColumn()) !== false) {
        assert(is_string($value));
        $matches[] = $value;
    }

    return $matches;
}

/** @return list<string> */
function get_punctuation_issues(string $text, bool $is_modisme): array
{
    $issues = [];
    if (
        substr_count($text, '(') !== substr_count($text, ')')
        || substr_count($text, '[') !== substr_count($text, ']')
    ) {
        $issues[] = 'brackets';
    }
    if ((str_contains($text, ' .') && !str_contains($text, ' …')) || preg_match('/ [,:;!?]/', $text) === 1) {
        $issues[] = 'space';
    }
    if (str_contains($text, '..') || str_contains($text, '……')) {
        $issues[] = 'double';
    }
    if (str_contains($text, '….') || (!$is_modisme && str_contains($text, '.…'))) {
        $issues[] = 'four';
    }
    if (preg_match('/::|;;|,,|--/', $text) === 1) {
        $issues[] = 'repeated';
    }
    if (preg_match('/[,;:]$/D', $text) === 1) {
        $issues[] = 'terminal';
    }
    if (preg_match('/[,.)?:;][a-zA-Z]/', $text) === 1 || (!$is_modisme && preg_match('/…[a-zA-Z]/', $text) === 1)) {
        $issues[] = 'punctuation_letters';
    }
    if ($is_modisme) {
        if (str_contains($text, "I'") || preg_match('/[a-z]I/', $text) === 1) {
            $issues[] = 'confusable';
        }
        if (
            substr_count($text, '"') % 2 !== 0
            || substr_count($text, '«') !== substr_count($text, '»')
            || substr_count($text, '“') !== substr_count($text, '”')
        ) {
            $issues[] = 'quotes';
        }
    }

    return $issues;
}

/**
 * Collects row-based checks in one streaming scan, preserving duplicates and row order.
 *
 * @return array<string, list<string>>
 */
function scan_punctuation_report_values(): array
{
    $keys = [
        'p_brackets', 'p_space', 'p_double', 'p_four', 'p_repeated', 'p_terminal', 'p_punctuation_letters',
        'm_confusable', 'm_brackets', 'm_quotes', 'm_space', 'm_double', 'm_four', 'm_repeated', 'm_terminal', 'm_punctuation_letters',
    ];
    $outputs = array_fill_keys($keys, []);
    $db = get_db();
    $buffered = $db->getAttribute(Mysql::ATTR_USE_BUFFERED_QUERY);
    $db->setAttribute(Mysql::ATTR_USE_BUFFERED_QUERY, false);
    $stmt = null;

    try {
        $stmt = db_query('SELECT `PAREMIOTIPUS`, `MODISME` FROM `00_PAREMIOTIPUS`');
        while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
            [$paremiotipus, $modisme] = $row;
            if (is_string($paremiotipus)) {
                foreach (get_punctuation_issues($paremiotipus, is_modisme: false) as $issue) {
                    $outputs['p_' . $issue][] = $paremiotipus;
                }
            }
            if (is_string($modisme)) {
                foreach (get_punctuation_issues($modisme, is_modisme: true) as $issue) {
                    $outputs['m_' . $issue][] = $modisme;
                }
            }
        }
    } finally {
        if ($stmt !== null) {
            $stmt->closeCursor();
        }
        $db->setAttribute(Mysql::ATTR_USE_BUFFERED_QUERY, $buffered);
    }

    return $outputs;
}

/** @param iterable<string> $values */
function show_punctuation_check(string $title, iterable $values, bool $display = false, bool $collapsed = false): void
{
    echo "<h3>{$title}</h3>";
    $lines = [];
    foreach ($values as $value) {
        $lines[] = $display ? get_paremiotipus_display($value, escape_html: false) : $value;
    }
    sort($lines, SORT_NATURAL | SORT_FLAG_CASE);
    $output = implode("\n", $lines) . "\n";
    if ($lines === []) {
        echo '<pre class="empty">(cap resultat)</pre>';
    } elseif ($collapsed) {
        echo "<details><pre>{$output}</pre></details>";
    } else {
        echo "<pre>{$output}</pre>";
    }
}

function test_puntuacio(): void
{
    require_once __DIR__ . '/../common.php';

    $paremiotipus = db_query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $modismes = db_query('SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS` ORDER BY `MODISME`')->fetchAll(PDO::FETCH_COLUMN);
    $scan_outputs = scan_punctuation_report_values();
    $show_distinct_check = static function (string $title, callable $matches, array $details = []) use ($paremiotipus, $modismes): void {
        foreach (
            [
                ['Paremiotipus', $paremiotipus, true],
                ['Modismes', $modismes, false],
            ] as [$type, $values, $display]
        ) {
            show_punctuation_check(
                "{$type} {$title}",
                array_filter($values, $matches),
                display: $display,
                collapsed: in_array($type, $details, true)
            );
        }
    };
    show_punctuation_check(
        'Paremiotipus amb parèntesis o claudàtors no tancats',
        $scan_outputs['p_brackets'],
        display: true
    );

    show_punctuation_check(
        'Paremiotipus amb cometes no tancades',
        db_query("SELECT `Display` FROM `paremiotipus_display` WHERE (LENGTH(`Display`) - LENGTH(REPLACE(`Display`, '\"', ''))) % 2 != 0 OR LENGTH(REPLACE(`Display`, '«', '')) != LENGTH(REPLACE(`Display`, '»', '')) OR LENGTH(REPLACE(`Display`, '“', '')) != LENGTH(REPLACE(`Display`, '”', ''))")->fetchAll(PDO::FETCH_COLUMN)
    );

    show_punctuation_check(
        'Paremiotipus amb cometa simple seguida del caràcter espai o signe de puntuació inusual',
        get_single_quote_punctuation_matches('PAREMIOTIPUS'),
        display: true
    );

    foreach (
        [
            'double' => 'amb 2 punts seguits',
            'four' => 'amb 4 punts seguits',
            'punctuation_letters' => 'amb signe de puntuació seguit de lletres',
            'repeated' => 'amb signes de puntuació repetits',
            'space' => 'amb el caràcter espai seguit de signe de puntuació inusual',
            'terminal' => 'acabats amb signe de puntuació inusual',
        ] as $issue => $title
    ) {
        show_punctuation_check('Paremiotipus ' . $title, $scan_outputs['p_' . $issue], display: true);
    }

    show_punctuation_check(
        'Paremiotipus amb una combinació de signes de puntuació inusual',
        get_unusual_punctuation_combinations('PAREMIOTIPUS'),
        display: true
    );

    $show_distinct_check(
        'amb espai després d\'un apòstrof d\'elisió',
        static fn (string $p): bool => str_contains($p, "'") && preg_match("/(?:^|[^A-Za-zÀ-ÖØ-ÝÇà-öø-ÿç])(?:[LldDnNsSmMtT]|[Qq]u)'\\s/u", $p) === 1,
        ['Modismes']
    );

    $show_distinct_check(
        'amb espai abans d\'un apòstrof',
        static fn (string $p): bool => str_contains($p, "'") && preg_match("/[A-Za-zÀ-ÖØ-ÝÇà-öø-ÿç]\\s'/u", $p) === 1,
        ['Modismes']
    );

    $show_distinct_check(
        'amb coma sense espai posterior',
        static fn (string $p): bool => str_contains($p, ',') && preg_match('/,[^\s\])\'".…]/u', $p) === 1,
        ['Modismes']
    );

    $show_distinct_check(
        'amb punts suspensius mal formats',
        static fn (string $p): bool => strpbrk($p, '.,') !== false && preg_match('/\.\s\.\s\.|\.{4,}|,\.\.\.|\.{3}(?=[A-Za-zÀ-ÖØ-ÝÇà-öø-ÿç])/u', $p) === 1
    );

    $show_distinct_check(
        'amb puntuació incorrecta després de signe d\'exclamació o interrogació',
        static fn (string $p): bool => strpbrk($p, '!?') !== false && str_contains($p, '-') && preg_match('/[!?]-/u', $p) === 1,
        ['Modismes']
    );

    $show_distinct_check(
        'amb claudàtors o parèntesis buits o adjacents',
        static fn (string $p): bool => strpbrk($p, '()[]') !== false && preg_match('/\[\s*\]|\(\s*\)|\)[[(]|\][[(]/u', $p) === 1
    );

    $show_distinct_check(
        'amb tres lletres idèntiques seguides',
        static fn (string $p): bool => preg_match('/([A-Za-zÀ-ÖØ-ÝÇà-öø-ÿç])\1\1/ui', preg_replace('/\b[IVXLCDM]+\b/ui', '', $p) ?? '') === 1,
        ['Paremiotipus', 'Modismes']
    );

    $show_distinct_check(
        'amb més d\'un accent gràfic en una paraula',
        static fn (string $p): bool => preg_match('/[àèéíòóúÀÈÉÍÒÓÚ][^\s,.;:!?()[\]"\'\-–—\/]*[àèéíòóúÀÈÉÍÒÓÚ]/u', $p) === 1,
        ['Paremiotipus', 'Modismes']
    );

    $show_distinct_check(
        'amb entitats HTML en text pla',
        static fn (string $p): bool => str_contains($p, '&') && preg_match('/&(?:amp|lt|gt|quot|apos|nbsp);|&#\d+;/iu', $p) === 1
    );

    $show_distinct_check(
        'amb l\'abreviatura etc sense punt',
        static fn (string $p): bool => stripos($p, 'etc') !== false && preg_match('/\betc(?!\.)\b/iu', $p) === 1,
        ['Modismes']
    );

    foreach (
        [
            'brackets' => 'amb parèntesis o claudàtors no tancats',
            'confusable' => 'amb possible confusió del caràcter <code>l</code> amb <code>I</code>',
            'double' => 'amb 2 punts seguits',
            'four' => 'amb 4 punts seguits',
            'quotes' => 'amb cometes no tancades',
            'repeated' => 'amb signes de puntuació repetits',
            'space' => 'amb el caràcter espai seguit de signe de puntuació inusual',
        ] as $issue => $title
    ) {
        show_punctuation_check('Modismes ' . $title, $scan_outputs['m_' . $issue], collapsed: $issue === 'space');
    }

    show_punctuation_check(
        'Modismes amb caràcters no reconeguts',
        db_query("SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` REGEXP '[гпичäȧ́ä́ãõâêôûćčłø~ˆ¨ßşšžźŧ]'")->fetchAll(PDO::FETCH_COLUMN),
        collapsed: true
    );
    show_punctuation_check(
        'Modismes acabats amb signe de puntuació inusual',
        $scan_outputs['m_terminal'],
        collapsed: true
    );
    show_punctuation_check(
        'Modismes amb una combinació de signes de puntuació inusual',
        get_unusual_punctuation_combinations('MODISME')
    );
    show_punctuation_check(
        'Modismes amb cometa simple seguida del caràcter espai o signe de puntuació inusual',
        get_single_quote_punctuation_matches('MODISME'),
        collapsed: true
    );
    show_punctuation_check(
        'Modismes amb signe de puntuació seguit de lletres',
        $scan_outputs['m_punctuation_letters'],
        collapsed: true
    );
}

function test_paremiotipus_caracters_inusuals(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus amb caràcters inusuals en català</h3>';
    $paremiotipus = db_query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        assert(is_string($p));
        $t = str_replace(
            ['à', 'è', 'é', 'í', 'ï', 'ò', 'ó', 'ú', 'ü', 'ç', '«', '»', '·', '–', '‑', '—', '―', '─', '…'],
            '',
            mb_strtolower($p)
        );
        if (
            // If it contains any non-ASCII character.
            preg_match('/[^\x00-\x7F]/', $t) === 1
        ) {
            $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }

    echo '<h3>Paremiotipus amb caràcters de guió o guionet no estàndards (ni — ni -)</h3>';
    $paremiotipus = db_query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $guions = [
        '‑' => [],
        '–' => [],
        '―' => [],
        '─' => [],
    ];
    $guions_keys = array_keys($guions);
    foreach ($paremiotipus as $p) {
        assert(is_string($p));
        foreach ($guions_keys as $guio) {
            if (str_contains($p, $guio)) {
                $guions[$guio][] = $p;
            }
        }
    }
    $output = '';
    foreach ($guions as $guio => $guio_array) {
        if ($guio_array === []) {
            continue;
        }
        $output .= "<strong>Caràcter {$guio}</strong>\n";
        foreach ($guio_array as $p) {
            $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
        }
        $output .= "\n\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes amb caràcters de guió o guionet no estàndards (ni — ni -)</h3>';
    $paremiotipus = db_query('SELECT `MODISME` FROM `00_PAREMIOTIPUS` ORDER BY `MODISME`')->fetchAll(PDO::FETCH_COLUMN);
    $guions = [
        '‑' => [],
        '–' => [],
        '―' => [],
        '─' => [],
    ];
    $guions_keys = array_keys($guions);
    foreach ($paremiotipus as $p) {
        assert(is_string($p));
        foreach ($guions_keys as $guio) {
            if (str_contains($p, $guio)) {
                $guions[$guio][] = $p;
            }
        }
    }
    $output = '';
    foreach ($guions as $guio => $guio_array) {
        if ($guio_array === []) {
            continue;
        }
        $output .= "<strong>Caràcter {$guio}</strong>\n";
        foreach ($guio_array as $p) {
            $output .= $p . "\n";
        }
        $output .= "\n\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}

function test_paremiotipus_final(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus que acaben amb caràcters no alfabètics o amb signe de puntuació inusual</h3>';
    $paremiotipus = db_query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        assert(is_string($p));
        $t = str_replace(
            ['à', 'è', 'é', 'í', 'ï', 'ò', 'ó', 'ú', 'ü', 'ç'],
            ['a', 'e', 'e', 'i', 'i', 'o', 'o', 'u', 'u', 'c'],
            mb_strtolower($p)
        );
        if (
            preg_match('/[a-z]$/', $t) === 0
            && !str_ends_with($t, '!')
            && !str_ends_with($t, '?')
            && !str_ends_with($t, '»')
            && !str_ends_with($t, '"')
            && !str_ends_with($t, '…')
        ) {
            $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}
