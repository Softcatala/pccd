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
    $sinonims = file_get_contents(__DIR__ . '/../../data/reports/softcatala_sinonims.txt');
    assert($sinonims !== false);
    $sinonims = str_replace('...', '…', $sinonims);

    $lines = explode("\n", $sinonims);
    $output_lines = [];
    $all_sentences_lines = [];
    $count = 0;

    foreach ($lines as $line) {
        $line = trim($line);
        $uc_line = mb_ucfirst($line);
        $all_sentences_lines[] = $uc_line;

        if (strlen($line) > 1) {
            $count++;
            if (!isset($modismes[mb_strtolower($line)])) {
                $output_lines[] = $uc_line;
            }
        }
    }

    $output_lines = array_unique($output_lines);
    sort($output_lines, SORT_STRING | SORT_FLAG_CASE);
    $output = implode("\n", $output_lines);

    echo '<h4>Sinònims amb espai que no existeixen com a modisme</h4>';
    echo 'Total: ' . format_nombre(count($output_lines));
    echo '<details><pre>';
    echo $output;
    echo '</pre></details>';

    echo '<h4>Totes les frases del diccionari, per grups de sinònims</h4>';
    echo 'Total: ' . format_nombre($count);
    echo '<details><pre>';
    echo implode("\n", $all_sentences_lines) . "\n";
    echo '</pre></details>';
}
