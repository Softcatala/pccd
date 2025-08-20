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
const SIMILAR_TEXT_MIN_LENGTH = 15;
const SIMILAR_TEXT_MAX_LENGTH = 32;

function test_paremiotipus_accents(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus amb diferències de majúscules, accents, o espais al principi/final</h3>';
    echo '<pre>';
    readfile(__DIR__ . '/../../tmp/test_paremiotipus_accents.txt');
    echo '</pre>';
}

function test_paremiotipus_modismes_diferents(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus diferents que contenen exactament el mateix modisme</h3>';
    echo '<pre>';
    $accents = '';
    $paremiotipus = get_db()->query('SELECT
            `a`.`PAREMIOTIPUS` as `PAREMIOTIPUS_A`,
            `a`.`MODISME` as `MODISME_A`,
            `b`.`PAREMIOTIPUS` as `PAREMIOTIPUS_B`,
            `b`.`MODISME` as `MODISME_B`
        FROM
            `00_PAREMIOTIPUS` `a`,
            `00_PAREMIOTIPUS` `b`
        WHERE
            `a`.`MODISME` = `b`.`MODISME`
        AND
            `a`.`PAREMIOTIPUS` != `b`.`PAREMIOTIPUS`')->fetchAll(PDO::FETCH_ASSOC);
    $paremiotipus_unics = [];
    foreach ($paremiotipus as $m) {
        assert(is_string($m['PAREMIOTIPUS_A']));
        assert(is_string($m['PAREMIOTIPUS_B']));
        if (!isset($paremiotipus_unics[$m['PAREMIOTIPUS_A']]) && !isset($paremiotipus_unics[$m['PAREMIOTIPUS_B']])) {
            if ($m['MODISME_A'] === $m['MODISME_B']) {
                echo get_paremiotipus_display($m['PAREMIOTIPUS_A'], escape_html: false) . ' (modisme: ' . $m['MODISME_A'] . ")\n";
                echo get_paremiotipus_display($m['PAREMIOTIPUS_B'], escape_html: false) . ' (modisme: ' . $m['MODISME_B'] . ")\n";
                echo "\n";
            } else {
                $accents .= get_paremiotipus_display($m['PAREMIOTIPUS_A'], escape_html: false) . ' (modisme: ' . $m['MODISME_A'] . ")\n";
                $accents .= get_paremiotipus_display($m['PAREMIOTIPUS_B'], escape_html: false) . ' (modisme: ' . $m['MODISME_B'] . ")\n";
                $accents .= "\n";
            }
        }
        $paremiotipus_unics[$m['PAREMIOTIPUS_A']] = true;
        $paremiotipus_unics[$m['PAREMIOTIPUS_B']] = true;
    }
    echo '</pre>';

    echo '<h3>Paremiotipus diferents que contenen un mateix modisme amb diferències de majúscules o accents (o espais al principi/final)</h3>';
    echo '<pre>';
    echo $accents;
    echo '</pre>';
}

function test_paremiotipus_repetits(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus molt semblants (consecutius)</h3>';
    echo '<pre>';
    $prev = '';
    $modismes = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        assert(is_string($m));
        $string1 = strtolower((string) preg_replace('#[[:punct:]]#', '', substr($m, SIMILAR_TEXT_MAX_LENGTH)));
        $string2 = strtolower((string) preg_replace('#[[:punct:]]#', '', substr($prev, SIMILAR_TEXT_MAX_LENGTH)));

        similar_text($string1, $string2, $percent);
        if (
            $percent > SIMILAR_TEXT_THRESHOLD_1
            || (
                $percent > SIMILAR_TEXT_THRESHOLD_2
                && strlen($string1) > SIMILAR_TEXT_MIN_LENGTH
            )
        ) {
            echo get_paremiotipus_display($prev, escape_html: false) . "\n" . get_paremiotipus_display($m, escape_html: false) . "\n\n";
        }
        $prev = $m;
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb diferències de caràcters que es poden confondre visualment (consecutius)</h3>';
    echo '<pre>';
    readfile(__DIR__ . '/../../tmp/test_intl_paremiotipus_repetits.txt');
    echo '</pre>';

    echo "<h3>Nous paremiotipus molt semblants des de l'última actualització (Levenshtein)</h3>";
    echo '<pre>';
    // Display the new pairs from git diff output.
    $lines = file(__DIR__ . '/../../tmp/test_repetits_new.txt');
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
                echo ltrim($prev, '+') . "\n";
                echo ltrim($line, '+') . "\n";
                echo "\n";
            }
            $prev = $line;
        }
    }
    echo '</pre>';

    echo '<h3>Paremiotipus molt semblants (Levenshtein)</h3>';
    echo '<details><pre>';
    readfile(__DIR__ . '/../../tmp/test_repetits.txt');
    echo '</pre></details>';
}
