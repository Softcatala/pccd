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

function string_has_consecutive_repeated_chars(string $str): bool
{
    $common_repetitions = ['cc', 'ee', 'll', 'mm', 'nn', 'oo', 'rr', 'ss', 'uu'];

    $last_char_pos = mb_strlen($str) - 1;
    for ($i = 0; $i < $last_char_pos; $i++) {
        $count = 1;
        while ($i < $last_char_pos && mb_substr($str, $i, 1) === mb_substr($str, $i + 1, 1)) {
            $count++;
            $i++;
        }

        if ($count > 1) {
            $repeated_string = mb_substr($str, $i + 1 - $count, $count);
            if (!in_array($repeated_string, $common_repetitions, true)) {
                return true;
            }
        }
    }

    return false;
}

function test_repeticio_caracters(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus amb una repetició de caràcters inusual</h3>';
    echo '<details><pre>';
    $modismes = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        assert(is_string($m));
        if (string_has_consecutive_repeated_chars($m)) {
            echo get_paremiotipus_display($m, escape_html: false) . "\n";
        }
    }
    echo '</pre></details>';

    echo '<h3>Modismes amb una repetició de caràcters inusual</h3>';
    echo '<details><pre>';
    $modismes = get_db()->query('SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        assert(is_string($m));
        if (string_has_consecutive_repeated_chars($m)) {
            echo $m . "\n";
        }
    }
    echo '</pre></details>';
}
