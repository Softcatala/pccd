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

function stats_autors(): void
{
    require_once __DIR__ . '/../common.php';

    require_once __DIR__ . '/../reports_common.php';

    echo '<script src="/admin/js/chart.min.js"></script>';

    echo '<h3>Principals autors pel nombre de paremiotipus</h3>';
    $records = get_db()->query('SELECT
            `AUTOR`,
            COUNT(DISTINCT `PAREMIOTIPUS`) AS `Paremiotipus`
        FROM
            `00_PAREMIOTIPUS`
        WHERE
            `AUTOR` IS NOT NULL
        GROUP BY
            `AUTOR`
        ORDER BY
            `Paremiotipus` DESC
    ')->fetchAll(PDO::FETCH_KEY_PAIR);
    $data = [];
    foreach ($records as $autor => $count) {
        if ($count >= 5000) {
            $data[$autor] = $count;
        }
    }
    echo get_chart('doughnut', $data, 'paremiotipus', style: 'width:800px;');
    echo '<details><summary>Mostra la llista completa</summary>';
    echo "<table style='width:1200px;'>";
    echo '<tr><th>Autor</th><th>Paremiotipus</th></tr>';
    foreach ($records as $autor => $paremiotipus) {
        assert(is_string($autor));
        echo '<tr>';
        echo '<td>' . htmlspecialchars($autor) . '</td>';
        echo '<td>' . format_nombre($paremiotipus) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</details>';

    echo '<h3>Principals autors pel nombre de fitxes</h3>';
    $records = get_db()->query('SELECT
            `AUTOR`,
            COUNT(1) AS `Fitxes`
        FROM
            `00_PAREMIOTIPUS`
        WHERE
            `AUTOR` IS NOT NULL
        GROUP BY
            `AUTOR`
        ORDER BY
            `Fitxes` DESC
    ')->fetchAll(PDO::FETCH_KEY_PAIR);
    $data = [];
    foreach ($records as $autor => $count) {
        if ($count >= 5000) {
            $data[$autor] = $count;
        }
    }
    echo get_chart('doughnut', $data, 'fitxes', style: 'width:800px;');
    echo '<details><summary>Mostra la llista completa</summary>';
    echo "<table style='width:1200px;'>";
    echo '<tr><th>Autor</th><th>Fitxes</th></tr>';
    foreach ($records as $autor => $fitxes) {
        assert(is_string($autor));
        echo '<tr>';
        echo '<td>' . htmlspecialchars($autor) . '</td>';
        echo '<td>' . format_nombre($fitxes) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</details>';

    echo "<h3>Principals autors pel nombre d'obres</h3>";
    $records = get_db()->query('SELECT
            `AUTOR`,
            COUNT(1) AS `Obres`
        FROM
            `00_FONTS`
        WHERE
            `AUTOR` IS NOT NULL
        GROUP BY
            `AUTOR`
        ORDER BY
            `Obres` DESC
    ')->fetchAll(PDO::FETCH_KEY_PAIR);
    $data = [];
    foreach ($records as $autor => $count) {
        if ($count >= 3) {
            $data[$autor] = $count;
        }
    }
    echo get_chart('doughnut', $data, 'obres', style: 'width:800px;');
    echo '<details><summary>Mostra la llista completa</summary>';
    echo "<table style='width:1200px;'>";
    echo "<tr><th>Autor</th><th>Nombre d'obres</th></tr>";
    foreach ($records as $autor => $obres) {
        assert(is_string($autor));
        echo '<tr>';
        echo '<td>' . htmlspecialchars($autor) . '</td>';
        echo '<td>' . format_nombre($obres) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</details>';
}
