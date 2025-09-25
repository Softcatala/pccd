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

/**
 * Outputs cached searches.
 */
function stats_cerques(): void
{
    require_once __DIR__ . '/../reports_common.php';

    if (function_exists('apcu_enabled') && apcu_enabled()) {
        $records = [];
        $records_assoc = [];
        $word_count_stats = [];
        foreach (new APCUIterator('/^ WHERE/') as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            if (!is_string($entry['key'])) {
                continue;
            }
            $key = $entry['key'];
            $last_par = mb_strrpos($key, '|');
            if ($last_par === false) {
                $last_par = mb_strrpos($key, ')');
            }
            if ($last_par !== false) {
                $key = mb_substr($key, $last_par + 1);
            }

            $key = str_replace(['+', '.'], ['', '?'], $key);
            $key = trim($key);

            if (isset($records[$key])) {
                continue;
            }

            // Count words.
            $words = str_word_count($key);
            if ($words >= 10) {
                $words = '10+';
            }
            if (!isset($word_count_stats[$words])) {
                $word_count_stats[$words] = 0;
            }
            $word_count_stats[$words]++;

            $records[$key] = $entry['value'];
            $records_assoc[$key] = [
                'results' => $entry['value'],
            ];
        }

        // Sort by number of records.
        asort($records, \SORT_NUMERIC);
        // Sort by key alphabetically, but only if values are equal.
        uksort($records, static function (string $key1, string $key2) use ($records): int {
            if ($records[$key1] === $records[$key2]) {
                return $key1 <=> $key2;
            }

            return 0;
        });

        // Sort by number of words, and keep 10+ at the end.
        ksort($word_count_stats, \SORT_NATURAL);

        echo '<script src="/admin/js/chart.min.js"></script>';

        echo '<h3>Cerques per nombre de resultats</h3>';
        $grouped_data = group_data_stats($records_assoc, 'results');
        echo get_chart('bar', $grouped_data, 'cerques', x_title: 'Nombre de resultats', y_title: 'Nombre de cerques úniques', style: 'width:1000px;');

        echo '<h3>Cerques per longitud</h3>';
        echo get_chart('bar', $word_count_stats, 'cerques', x_title: 'Nombre de paraules cercades', y_title: 'Nombre de cerques', style: 'width:1000px;');

        echo "Total de cerques úniques des de l'últim desplegament: " . count($records);
        echo '<details><summary>Mostra totes les cerques</summary>';
        echo "<table style='width:1000px;'>";
        echo '<tr><th>Cerca</th><th>Resultats</th></tr>';
        foreach ($records as $cerca => $resultats) {
            echo "<tr><td>{$cerca}</td><td>" . format_nombre($resultats) . '</td></tr>';
        }
        echo '</table>';
        echo '</details>';
    } else {
        echo '<strong>Error: APCu is not enabled</strong>';
    }
}
