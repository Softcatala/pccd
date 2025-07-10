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

function test_deiec(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Diccionari essencial de la llengua catalana (DEIEC)</h3>';

    $modismes = get_db()->query('SELECT DISTINCT BINARY LOWER(`MODISME`), 1 FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_KEY_PAIR);
    $locucions = file_get_contents(__DIR__ . '/deiec_locucions.txt');
    $variants_formals = file_get_contents(__DIR__ . '/deiec_vf.txt');
    assert($locucions !== false);
    assert($variants_formals !== false);
    $locucions = str_replace('...', '…', $locucions);
    $variants_formals = str_replace('...', '…', $variants_formals);

    $lines = explode("\n", $locucions);
    $output_lines = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== 'undefined' && strlen($line) > 1 && !isset($modismes[mb_strtolower($line)])) {
            $output_lines[] = mb_ucfirst($line);
        }
    }
    sort($output_lines, SORT_STRING | SORT_FLAG_CASE);
    $output_lines = array_unique($output_lines);
    $output = implode("\n", $output_lines);
    echo '<h4>Locucions que no existeixen com a modisme</h4>';
    echo '<i>En alguns casos, potser existeixen com a paremiotipus</i>';
    echo '<br>';
    echo 'Total: ' . format_nombre(count($output_lines));
    echo '<details><pre>';
    echo $output;
    echo '</pre></details>';

    $lines = explode("\n", $variants_formals);
    $output_lines = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== 'undefined' && strlen($line) > 1 && !isset($modismes[mb_strtolower($line)])) {
            $output_lines[] = mb_ucfirst($line);
        }
    }
    sort($output_lines, SORT_STRING | SORT_FLAG_CASE);
    $output_lines = array_unique($output_lines);
    $output = implode("\n", $output_lines);
    echo '<h4>Variants formals que no existeixen com a modisme</h4>';
    echo 'Total: ' . format_nombre(count($output_lines));
    echo '<details><pre>';
    echo $output;
    echo '</pre></details>';
}
