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

function stats_obres(): void
{
    require_once __DIR__ . '/../common.php';

    require_once __DIR__ . '/../reports_common.php';

    echo '<script src="/admin/js/chart.min.js"></script>';
    echo '<h3>Creixement històric de la base de dades</h3>';
    $directory_path = __DIR__ . '/../../tests/playwright/data/historic/';
    $files = scandir($directory_path);
    assert(is_array($files));
    $fonts_number_data = get_data_from_files($files, $directory_path, 'fontsNumber');
    echo get_chart('line', $fonts_number_data, 'fonts', 'Mesos (2023-)', 'Nombre de fonts', style: 'width:800px;');

    echo "<h3>Obres ordenades pel nombre d'entrades a la base de dades</h3>";
    $records = get_db()->query('SELECT
            00_FONTS.`Identificador` as Font,
            00_FONTS.`Registres` as Registres,
            COUNT(00_PAREMIOTIPUS.`ID_FONT`) AS NumberOfReferences
        FROM
            00_FONTS
        LEFT JOIN
            00_PAREMIOTIPUS ON 00_FONTS.`Identificador` = 00_PAREMIOTIPUS.`ID_FONT`
        GROUP BY
            00_FONTS.`Identificador`,
            00_FONTS.`Registres`
        ORDER BY
            NumberOfReferences DESC
    ')->fetchAll(PDO::FETCH_ASSOC);
    echo "<table style='width:1200px;'>";
    echo '<tr><th>Obra</th><th>Total</th><th>Recollides</th><th>Falten</th></tr>';
    foreach ($records as $r) {
        assert(is_string($r['Registres']));
        assert(is_string($r['NumberOfReferences']));
        assert(is_string($r['Font']));
        echo '<tr>';
        echo '<td><a href="' . get_obra_url($r['Font']) . '">' . htmlspecialchars($r['Font']) . '</a></td>';
        echo '<td>' . format_nombre($r['Registres']) . '</td>';
        echo '<td><a title="Mostra els paremiotipus" href="/?font=' . name_to_slug($r['Font']) . '">' . format_nombre($r['NumberOfReferences']) . '</a></td>';
        echo '<td>' . format_nombre(((int) $r['Registres']) - ((int) $r['NumberOfReferences'])) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}
