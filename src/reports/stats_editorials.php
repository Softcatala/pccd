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

function stats_editorials(): void
{
    require_once __DIR__ . '/../common.php';

    require_once __DIR__ . '/../reports_common.php';

    $editorials = get_editorials();
    echo '<script src="/admin/js/chart.min.js"></script>';

    echo '<h3>Principals editorials pel nombre de paremiotipus</h3>';
    $records = get_db()->query('SELECT
            `EDITORIAL`,
            COUNT(DISTINCT `PAREMIOTIPUS`) AS `Repetitions`
        FROM
            `00_PAREMIOTIPUS`
        WHERE
            `EDITORIAL` IS NOT NULL
        GROUP BY
            `EDITORIAL`
        ORDER BY
            `Repetitions` DESC
    ')->fetchAll(PDO::FETCH_KEY_PAIR);
    $data = [];
    $data_table_paremiotipus = [];
    foreach ($records as $ed_key => $count) {
        if (!isset($editorials[$ed_key])) {
            continue;
        }

        if (count($data) <= 25) {
            $data[$editorials[$ed_key]] = $count;
        }
        $data_table_paremiotipus[$editorials[$ed_key]] = $count;
    }
    echo get_chart('doughnut', $data, 'paremiotipus', style: 'width:600px;');

    echo '<details><summary>Mostra la llista completa</summary>';
    echo "<table style='width:600px;'>";
    echo '<tr><th>Editorial</th><th>Paremiotipus</th></tr>';
    foreach ($data_table_paremiotipus as $editorial => $count) {
        echo "<tr><td>{$editorial}</td><td>" . format_nombre($count) . '</td></tr>';
    }
    echo '</table>';
    echo '</details>';

    echo '<h3>Principals editorials pel nombre de fitxes</h3>';
    $records = get_db()->query('SELECT
            `EDITORIAL`,
            COUNT(1) AS `Repetitions`
        FROM
            `00_PAREMIOTIPUS`
        WHERE
            `EDITORIAL` IS NOT NULL
        GROUP BY
            `EDITORIAL`
        ORDER BY
            `Repetitions` DESC
    ')->fetchAll(PDO::FETCH_KEY_PAIR);

    $data = [];
    $data_table = [];
    foreach ($records as $ed_key => $count) {
        if (!isset($editorials[$ed_key])) {
            continue;
        }

        if (count($data) <= 25) {
            $data[$editorials[$ed_key]] = $count;
        }
        $data_table[$editorials[$ed_key]] = $count;
    }
    echo get_chart('doughnut', $data, 'fitxes', style: 'width:600px;');

    echo '<details><summary>Mostra la llista completa</summary>';
    echo "<table style='width:600px;'>";
    echo '<tr><th>Editorial</th><th>Fitxes</th></tr>';
    foreach ($data_table as $editorial => $count) {
        echo "<tr><td>{$editorial}</td><td>" . format_nombre($count) . '</td></tr>';
    }
    echo '</table>';
    echo '</details>';

    echo "<h3>Principals editorials pel nombre d'obres</h3>";
    $records = get_db()->query('SELECT
            `EDITORIAL`,
            COUNT(1) AS `Obres`
        FROM
            `00_FONTS`
        WHERE
            `EDITORIAL` IS NOT NULL
        GROUP BY
            `EDITORIAL`
        ORDER BY
            `Obres` DESC
    ')->fetchAll(PDO::FETCH_KEY_PAIR);
    $data = [];
    foreach ($records as $editorial => $count) {
        if ($count >= 4) {
            $data[$editorial] = $count;
        }
    }
    echo get_chart('doughnut', $data, 'obres', style: 'width:600px;');
    echo '<details><summary>Mostra la llista completa</summary>';
    echo "<table style='width:1200px;'>";
    echo "<tr><th>Editorial</th><th>Nombre d'obres</th></tr>";
    foreach ($records as $editorial => $obres) {
        assert(is_string($editorial));
        echo '<tr>';
        echo '<td>' . htmlspecialchars($editorial) . '</td>';
        echo '<td>' . format_nombre($obres) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</details>';
}
