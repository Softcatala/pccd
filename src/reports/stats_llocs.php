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

function stats_llocs(): void
{
    require_once __DIR__ . '/../common.php';

    require_once __DIR__ . '/../reports_common.php';

    $records = get_db()->query('
        SELECT
            `LLOC`,
            COUNT(`LLOC`) AS `Repetitions`
        FROM
            `00_PAREMIOTIPUS`
        GROUP BY
            `LLOC`
        ORDER BY
            `Repetitions` DESC,
            `LLOC` ASC;
    ')->fetchAll(PDO::FETCH_KEY_PAIR);

    echo '<h3>Llocs ordenats per freqüència</h3>';
    echo "<table style='width:600px;'>";
    echo '<tr><th>Lloc</th><th>Entrades</th></tr>';
    foreach ($records as $lloc => $count) {
        if ($count > 0) {
            echo "<tr><td>{$lloc}</td><td>" . format_nombre($count) . '</td></tr>';
        }
    }
    echo '</table>';
}
