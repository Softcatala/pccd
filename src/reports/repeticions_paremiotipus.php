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

const SIMILAR_TEXT_THRESHOLD_1 = 85;
const SIMILAR_TEXT_THRESHOLD_2 = 75;
const SIMILAR_TEXT_MIN_LENGTH = 16;
const SIMILAR_TEXT_MAX_LENGTH = 32;

function test_paremiotipus_accents(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus amb diferències de majúscules, accents, o espais al principi/final</h3>';
    $output = trim((string) @file_get_contents(__DIR__ . '/../../data/reports/test_paremiotipus_accents.txt'));
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details open><pre>{$output}</pre></details>";
    }
}

/**
 * Yields shared modismes, each grouped by paremiotipus with its exact spelling variants.
 *
 * @return Generator<int, list<list<array{0: string, 1: string}>>>
 */
function get_shared_modisme_groups(): Generator
{
    // Only fetch modismes shared by different paremiotipus, using the database collation.
    // Weight strings preserve that grouping in PHP; binary values retain exact spellings.
    $rows = db_query('
        SELECT DISTINCT
            WEIGHT_STRING(`p`.`MODISME`),
            WEIGHT_STRING(`p`.`PAREMIOTIPUS`),
            BINARY `p`.`PAREMIOTIPUS`,
            BINARY `p`.`MODISME`
        FROM `00_PAREMIOTIPUS` `p`
        INNER JOIN (
            SELECT `MODISME`
            FROM `00_PAREMIOTIPUS`
            WHERE `MODISME` IS NOT NULL AND `PAREMIOTIPUS` IS NOT NULL
            GROUP BY `MODISME`
            HAVING COUNT(DISTINCT `PAREMIOTIPUS`) > 1
        ) `shared` ON `p`.`MODISME` = `shared`.`MODISME`
        WHERE `p`.`PAREMIOTIPUS` IS NOT NULL
        ORDER BY `p`.`MODISME`, BINARY `p`.`MODISME`, BINARY `p`.`PAREMIOTIPUS`
    ');
    $group_key = null;
    $group = [];
    while (($row = $rows->fetch(PDO::FETCH_NUM)) !== false) {
        [$modisme_key, $paremiotipus_key, $paremiotipus_value, $modisme] = $row;
        assert(is_string($modisme_key));
        assert(is_string($paremiotipus_key));
        assert(is_string($paremiotipus_value));
        assert(is_string($modisme));
        if ($group_key !== null && $group_key !== $modisme_key) {
            yield array_values($group);

            $group = [];
        }
        $group_key = $modisme_key;
        $group['p:' . $paremiotipus_key][] = [$paremiotipus_value, $modisme];
    }
    if ($group !== []) {
        yield array_values($group);
    }
}

function test_paremiotipus_modismes_diferents(): void
{
    require_once __DIR__ . '/../common.php';

    $output = '';
    $accents = '';
    $seen_pairs = [];
    foreach (get_shared_modisme_groups() as $group) {
        $count = count($group);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                foreach ($group[$i] as [$paremiotipus_a, $modisme_a]) {
                    foreach ($group[$j] as [$paremiotipus_b, $modisme_b]) {
                        // Report each unordered pair once, even if it shares several modismes.
                        $pair_key = $paremiotipus_a < $paremiotipus_b
                            ? $paremiotipus_a . '|' . $paremiotipus_b
                            : $paremiotipus_b . '|' . $paremiotipus_a;
                        if (isset($seen_pairs[$pair_key])) {
                            continue;
                        }
                        $seen_pairs[$pair_key] = true;
                        $pair_output = get_paremiotipus_display($paremiotipus_a, escape_html: false) . ' (modisme: ' . $modisme_a . ")\n"
                            . get_paremiotipus_display($paremiotipus_b, escape_html: false) . ' (modisme: ' . $modisme_b . ")\n\n";
                        if ($modisme_a === $modisme_b) {
                            $output .= $pair_output;
                        } else {
                            $accents .= $pair_output;
                        }
                    }
                }
            }
        }
    }

    echo '<h3>Paremiotipus diferents que contenen exactament el mateix modisme</h3>';
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details open><pre>{$output}</pre></details>";
    }

    echo '<h3>Paremiotipus diferents que contenen un mateix modisme amb diferències de majúscules o accents (o espais al principi/final)</h3>';
    if ($accents === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details open><pre>{$accents}</pre></details>";
    }
}

function test_paremiotipus_repetits(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus molt semblants (consecutius)</h3>';
    $prev = '';
    $prev_normalized = '';
    $modismes = db_query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $normalized = strtolower(substr($m, 0, SIMILAR_TEXT_MAX_LENGTH));
        if ($prev_normalized !== '') {
            similar_text($prev_normalized, $normalized, $percent);
            if (
                $percent > SIMILAR_TEXT_THRESHOLD_1
                || (
                    $percent > SIMILAR_TEXT_THRESHOLD_2
                    && strlen($normalized) > SIMILAR_TEXT_MIN_LENGTH
                )
            ) {
                $output .= get_paremiotipus_display($prev, escape_html: false) . "\n";
                $output .= get_paremiotipus_display($m, escape_html: false) . "\n\n";
            }
        }
        $prev = $m;
        $prev_normalized = $normalized;
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }

    echo '<h3>Paremiotipus amb diferències de caràcters que es poden confondre visualment (consecutius)</h3>';
    $output = trim((string) @file_get_contents(__DIR__ . '/../../data/reports/test_intl_paremiotipus_repetits.txt'));
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details open><pre>{$output}</pre></details>";
    }

    echo "<h3>Nous paremiotipus molt semblants des de l'última actualització (Levenshtein)</h3>";
    $output = trim((string) @file_get_contents(__DIR__ . '/../../data/reports/test_repetits_new.txt'));
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details open><pre>{$output}</pre></details>";
    }

    echo '<h3>Paremiotipus molt semblants (Levenshtein)</h3>';
    $output = trim((string) @file_get_contents(__DIR__ . '/../../data/reports/test_repetits.txt'));
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }
}
