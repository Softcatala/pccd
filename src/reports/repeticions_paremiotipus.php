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

function test_paremiotipus_modismes_diferents(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus diferents que contenen exactament el mateix modisme</h3>';
    $accents = '';
    $output = '';
    $paremiotipus = db_query('
        SELECT
            `a`.`PAREMIOTIPUS`   as `P_A`,
            `a`.`MODISME`        as `M_A`,
            `b`.`PAREMIOTIPUS`   as `P_B`,
            `b`.`MODISME`        as `M_B`
        FROM
            `00_PAREMIOTIPUS` `a`,
            `00_PAREMIOTIPUS` `b`
        WHERE
            `a`.`MODISME` = `b`.`MODISME`
        AND
            `a`.`PAREMIOTIPUS` != `b`.`PAREMIOTIPUS`
    ')->fetchAll(PDO::FETCH_ASSOC);
    $seen_pairs = [];
    foreach ($paremiotipus as $m) {
        $pair_key = $m['P_A'] < $m['P_B'] ? ($m['P_A'] . '|' . $m['P_B']) : ($m['P_B'] . '|' . $m['P_A']);

        if (!isset($seen_pairs[$pair_key])) {
            $seen_pairs[$pair_key] = true;
            if ($m['M_A'] === $m['M_B']) {
                $output .= get_paremiotipus_display($m['P_A'], escape_html: false) . ' (modisme: ' . $m['M_A'] . ")\n";
                $output .= get_paremiotipus_display($m['P_B'], escape_html: false) . ' (modisme: ' . $m['M_B'] . ")\n";
                $output .= "\n";
            } else {
                // Rely on DB Collation to detect these and show them below.
                $accents .= get_paremiotipus_display($m['P_A'], escape_html: false) . ' (modisme: ' . $m['M_A'] . ")\n";
                $accents .= get_paremiotipus_display($m['P_B'], escape_html: false) . ' (modisme: ' . $m['M_B'] . ")\n";
                $accents .= "\n";
            }
        }
    }
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
    $lines = file(__DIR__ . '/../../data/reports/test_repetits_new.txt');
    $output = '';
    if ($lines !== false) {
        $prev = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if (
                strlen($prev) > 1
                && strlen($line) > 1
                && (
                    str_starts_with($prev, '+')
                    || str_starts_with($line, '+')
                )
            ) {
                $output .= ltrim($prev, '+') . "\n";
                $output .= ltrim($line, '+') . "\n";
                $output .= "\n";
            }
            $prev = $line;
        }
    }
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
