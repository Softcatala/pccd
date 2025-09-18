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

function test_softcatala_sinonims(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Diccionari de Sinònims de Softcatalà</h3>';

    $modismes = get_db()->query('SELECT DISTINCT BINARY LOWER(`MODISME`), 1 FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_KEY_PAIR);
    $sinonims = file_get_contents(__DIR__ . '/softcatala_sinonims.txt');
    assert($sinonims !== false);
    $sinonims = str_replace('...', '…', $sinonims);

    $lines = explode("\n", $sinonims);
    $output_lines = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (strlen($line) > 1 && !isset($modismes[mb_strtolower($line)])) {
            $output_lines[] = mb_ucfirst($line);
        }
    }

    sort($output_lines, SORT_STRING | SORT_FLAG_CASE);
    $output_lines = array_unique($output_lines);
    $output = implode("\n", $output_lines);

    echo '<h4>Sinònims amb espai que no existeixen com a modisme</h4>';
    echo 'Total: ' . format_nombre(count($output_lines));
    echo '<details><pre>';
    echo $output;
    echo '</pre></details>';

    $all_sentences = '';
    $count = 0;
    foreach ($lines as $line) {
        $line = trim($line);
        $all_sentences .= mb_ucfirst($line) . "\n";
        if (strlen($line) > 1) {
            $count++;
        }
    }
    echo '<h4>Totes les frases del diccionari, per grups de sinònims</h4>';
    echo 'Total: ' . format_nombre($count);
    echo '<details><pre>';
    echo $all_sentences;
    echo '</pre></details>';
}
