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

const MIN_RECORDS = 500;

function stats_equivalents(): void
{
    require_once __DIR__ . '/../common.php';

    require_once __DIR__ . '/../reports_common.php';

    echo '<script src="/admin/js/chart.min.js"></script>';
    echo "<h3>Nombre d'equivalents dels idiomes amb més de " . MIN_RECORDS . ' registres</h3>';
    $modismes = get_db()->query("SELECT
            COUNT(`EQUIVALENT`) AS `EQUIVALENTS`,
            IFNULL(`IDIOMA`, '(buit)') AS `IDIOMA`
        FROM
            `00_PAREMIOTIPUS`
        WHERE
            `EQUIVALENT` IS NOT NULL
        GROUP BY
            `IDIOMA`
    ")->fetchAll(PDO::FETCH_ASSOC);
    $data = [
        '(resta)' => 0,
    ];
    $data_table = [];
    foreach ($modismes as $modisme) {
        assert(is_string($modisme['EQUIVALENTS']));
        $language = $modisme['IDIOMA'] === '(buit)' ? '(buit)' : get_idioma($modisme['IDIOMA']);
        if ($language === '') {
            $data_table['(desconegut)'] = (int) $modisme['EQUIVALENTS'];
        } elseif ($modisme['EQUIVALENTS'] < MIN_RECORDS) {
            $data['(resta)'] += (int) $modisme['EQUIVALENTS'];
            $data_table[$language] = (int) $modisme['EQUIVALENTS'];
        } else {
            $data[$language] = (int) $modisme['EQUIVALENTS'];
            $data_table[$language] = (int) $modisme['EQUIVALENTS'];
        }
    }
    echo get_chart('bar', $data, 'equivalents', style: 'width:600px;');

    echo "<h3>Idiomes ordenats pel nombre d'equivalents</h3>";
    echo "<table style='width:300px;'>";
    echo '<tr><th>Idioma</th><th>Equivalents</th></tr>';
    arsort($data_table);
    foreach ($data_table as $language => $count) {
        echo "<tr><td>{$language}</td><td>" . format_nombre($count) . '</td></tr>';
    }
    echo '</table>';
}
