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

function test_puntuacio(): void
{
    require_once __DIR__ . '/../common.php';

    $paremiotipus = db_query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $modismes = db_query('SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS` ORDER BY `MODISME`')->fetchAll(PDO::FETCH_COLUMN);
    $show_paremiotipus_check = static function (string $title, callable $matches, array $details = []) use ($paremiotipus, $modismes): void {
        foreach (
            [
                ['Paremiotipus', $paremiotipus, true],
                ['Modismes', $modismes, false],
            ] as [$type, $values, $display]
        ) {
            echo "<h3>{$type} {$title}</h3>";
            $output = '';
            foreach ($values as $value) {
                assert(is_string($value));
                if ($matches($value)) {
                    $output .= ($display ? get_paremiotipus_display($value, escape_html: false) : $value) . "\n";
                }
            }
            if ($output === '') {
                echo '<pre class="empty">(cap resultat)</pre>';
            } elseif (in_array($type, $details, true)) {
                echo "<details><pre>{$output}</pre></details>";
            } else {
                echo "<pre>{$output}</pre>";
            }
        }
    };

    echo '<h3>Paremiotipus amb parèntesis o claudàtors no tancats</h3>';
    $paremiotipus = db_query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE LENGTH(REPLACE(`PAREMIOTIPUS`, '(', '')) != LENGTH(REPLACE(`PAREMIOTIPUS`, ')', '')) OR LENGTH(REPLACE(`PAREMIOTIPUS`, '[', '')) != LENGTH(REPLACE(`PAREMIOTIPUS`, ']', ''))")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus amb cometes no tancades</h3>';
    $paremiotipus = db_query("SELECT `Display` FROM `paremiotipus_display` WHERE (LENGTH(`Display`) - LENGTH(REPLACE(`Display`, '\"', ''))) % 2 != 0 OR LENGTH(REPLACE(`Display`, '«', '')) != LENGTH(REPLACE(`Display`, '»', '')) OR LENGTH(REPLACE(`Display`, '“', '')) != LENGTH(REPLACE(`Display`, '”', ''))")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= $p . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus amb cometa simple seguida del caràcter espai o signe de puntuació inusual</h3>';
    $paremiotipus = db_query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE (`PAREMIOTIPUS` LIKE '%\\' %' OR `PAREMIOTIPUS` LIKE '%\\'.%' OR `PAREMIOTIPUS` LIKE '%\\',%' OR `PAREMIOTIPUS` LIKE '%\\';%' OR `PAREMIOTIPUS` LIKE '%\\':%' OR `PAREMIOTIPUS` LIKE '%\\'-%') AND (LENGTH(`PAREMIOTIPUS`) - LENGTH(REPLACE(`PAREMIOTIPUS`, '\\'', ''))) = 1")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus amb el caràcter espai seguit de signe de puntuació inusual</h3>';
    $paremiotipus = db_query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE (`PAREMIOTIPUS` LIKE '% .%' AND `PAREMIOTIPUS` NOT LIKE '% …%') OR `PAREMIOTIPUS` LIKE '% ,%' OR `PAREMIOTIPUS` LIKE '% ;%' OR `PAREMIOTIPUS` LIKE '% :%' OR `PAREMIOTIPUS` LIKE '% !%' OR `PAREMIOTIPUS` LIKE '% ?%'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus amb 2 punts seguits</h3>';
    $paremiotipus = db_query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%..%' OR `PAREMIOTIPUS` LIKE '%……%'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus amb 4 punts seguits</h3>';
    $paremiotipus = db_query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%….%' OR `PAREMIOTIPUS` LIKE '%.…%'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus amb signes de puntuació repetits</h3>';
    $paremiotipus = db_query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%::%' OR `PAREMIOTIPUS` LIKE '%;;%' OR `PAREMIOTIPUS` LIKE '%,,%' OR `PAREMIOTIPUS` LIKE '%--%' OR `PAREMIOTIPUS` LIKE '%;;%'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus acabats amb signe de puntuació inusual</h3>';
    $paremiotipus = db_query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%,' OR `PAREMIOTIPUS` LIKE '%;' OR `PAREMIOTIPUS` LIKE '%:'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus amb signe de puntuació seguit de lletres</h3>';
    $paremiotipus = db_query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE (`PAREMIOTIPUS` REGEXP BINARY ',[a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY '[.][a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY '[)][a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY '[?][a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY ':[a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY ';[a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY '…[a-zA-Z]+')")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus amb una combinació de signes de puntuació inusual</h3>';
    $output = '';
    $paremiotipus = db_query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%?¿%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    $paremiotipus = db_query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%,?%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    $paremiotipus = db_query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%,!'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    $paremiotipus = db_query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%!.'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    $paremiotipus = db_query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%!…'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    $paremiotipus = db_query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%?.'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    $paremiotipus = db_query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%?…'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        $output .= get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    $show_paremiotipus_check(
        'amb espai després d\'un apòstrof d\'elisió',
        static fn (string $p): bool => preg_match("/(?:^|[^A-Za-zÀ-ÖØ-ÝÇà-öø-ÿç])(?:[LldDnNsSmMtT]|[Qq]u)'\\s/u", $p) === 1,
        ['Modismes']
    );

    $show_paremiotipus_check(
        'amb espai abans d\'un apòstrof',
        static fn (string $p): bool => preg_match("/[A-Za-zÀ-ÖØ-ÝÇà-öø-ÿç]\\s'/u", $p) === 1,
        ['Modismes']
    );

    $show_paremiotipus_check(
        'amb coma sense espai posterior',
        static fn (string $p): bool => preg_match('/,[^\s\])\'".…]/u', $p) === 1,
        ['Modismes']
    );

    $show_paremiotipus_check(
        'amb punts suspensius mal formats',
        static fn (string $p): bool => preg_match('/\.\s\.\s\.|\.{4,}|,\.\.\.|\.{3}(?=[A-Za-zÀ-ÖØ-ÝÇà-öø-ÿç])/u', $p) === 1
    );

    $show_paremiotipus_check(
        'amb puntuació incorrecta després de signe d\'exclamació o interrogació',
        static fn (string $p): bool => preg_match('/[!?]-/u', $p) === 1,
        ['Modismes']
    );

    $show_paremiotipus_check(
        'amb claudàtors o parèntesis buits o adjacents',
        static fn (string $p): bool => preg_match('/\[\s*\]|\(\s*\)|\)[[(]|\][[(]/u', $p) === 1
    );

    $show_paremiotipus_check(
        'amb tres lletres idèntiques seguides',
        static fn (string $p): bool => preg_match('/([A-Za-zÀ-ÖØ-ÝÇà-öø-ÿç])\1\1/ui', preg_replace('/\b[IVXLCDM]+\b/ui', '', $p) ?? '') === 1,
        ['Paremiotipus', 'Modismes']
    );

    $show_paremiotipus_check(
        'amb més d\'un accent gràfic en una paraula',
        static function (string $p): bool {
            $words = preg_split('/[\s,.;:!?()[\]"\'\-–—\/]+/u', $p, -1, PREG_SPLIT_NO_EMPTY);
            if ($words === false) {
                return false;
            }
            foreach ($words as $word) {
                if (preg_match_all('/[àèéíòóúÀÈÉÍÒÓÚ]/u', $word) > 1) {
                    return true;
                }
            }

            return false;
        },
        ['Paremiotipus', 'Modismes']
    );

    $show_paremiotipus_check(
        'amb entitats HTML en text pla',
        static fn (string $p): bool => preg_match('/&(?:amp|lt|gt|quot|apos|nbsp);|&#\d+;/iu', $p) === 1
    );

    $show_paremiotipus_check(
        'amb l\'abreviatura etc sense punt',
        static fn (string $p): bool => preg_match('/\betc(?!\.)\b/iu', $p) === 1,
        ['Modismes']
    );

    echo '<h3>Modismes amb possible confusió del caràcter <code>l</code> amb <code>I</code></h3>';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE BINARY '%I\\'%' OR `MODISME` REGEXP BINARY '[a-z]+I'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes amb parèntesis o claudàtors no tancats</h3>';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE LENGTH(REPLACE(`MODISME`, '(', '')) != LENGTH(REPLACE(`MODISME`, ')', '')) OR LENGTH(REPLACE(`MODISME`, '[', '')) != LENGTH(REPLACE(`MODISME`, ']', ''))")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes amb cometes no tancades</h3>';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE (LENGTH(`MODISME`) - LENGTH(REPLACE(`MODISME`, '\"', ''))) % 2 != 0 OR LENGTH(REPLACE(`MODISME`, '«', '')) != LENGTH(REPLACE(`MODISME`, '»', '')) OR LENGTH(REPLACE(`MODISME`, '“', '')) != LENGTH(REPLACE(`MODISME`, '”', ''))")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes amb el caràcter espai seguit de signe de puntuació inusual</h3>';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE (`MODISME` LIKE '% .%' AND `MODISME` NOT LIKE '% …%') OR `MODISME` LIKE '% ,%' OR `MODISME` LIKE '% ;%' OR `MODISME` LIKE '% :%' OR `MODISME` LIKE '% !%' OR `MODISME` LIKE '% ?%'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }

    echo '<h3>Modismes amb 2 punts seguits</h3>';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%..%' OR `MODISME` LIKE '%……%'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes amb 4 punts seguits</h3>';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%….%'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes amb signes de puntuació repetits</h3>';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%::%' OR `MODISME` LIKE '%;;%' OR `MODISME` LIKE '%,,%' OR `MODISME` LIKE '%--%' OR `MODISME` LIKE '%;;%'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes amb caràcters no reconeguts</h3>';
    $modismes = db_query("SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` REGEXP '[гпичäȧ́ä́ãõâêôûćčłø~ˆ¨ßşšžźŧ]'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }

    echo '<h3>Modismes acabats amb signe de puntuació inusual</h3>';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%,' OR `MODISME` LIKE '%;' OR `MODISME` LIKE '%:'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }

    echo '<h3>Modismes amb una combinació de signes de puntuació inusual</h3>';
    $output = '';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%?¿%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%,?%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%,!'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%!.'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%!…'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%?.'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%?…'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes amb cometa simple seguida del caràcter espai o signe de puntuació inusual</h3>';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE (`MODISME` LIKE '%\\' %' OR `MODISME` LIKE '%\\'.%' OR `MODISME` LIKE '%\\',%' OR `MODISME` LIKE '%\\';%' OR `MODISME` LIKE '%\\':%' OR `MODISME` LIKE '%\\'-%') AND (LENGTH(`MODISME`) - LENGTH(REPLACE(`MODISME`, '\\'', ''))) = 1")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }

    echo '<h3>Modismes amb signe de puntuació seguit de lletres</h3>';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE (`MODISME` REGEXP BINARY ',[a-zA-Z]+') OR (`MODISME` REGEXP BINARY '[.][a-zA-Z]+') OR (`MODISME` REGEXP BINARY '[)][a-zA-Z]+') OR (`MODISME` REGEXP BINARY '[?][a-zA-Z]+') OR (`MODISME` REGEXP BINARY ':[a-zA-Z]+') OR (`MODISME` REGEXP BINARY ';[a-zA-Z]+')")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }
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
