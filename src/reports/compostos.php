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

function test_paremies_separar(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Parèmies que probablement es poden separar en dues</h3>';
    $paremies = db_query("SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%(o%' OR `MODISME` LIKE '%[o%' ORDER BY `MODISME`")->fetchAll(PDO::FETCH_COLUMN);

    $output = '';
    foreach ($paremies as $m) {
        $output .= $m . "\n";
    }

    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo '<p>Total: ' . count($paremies) . '</p>';
        echo "<pre>{$output}</pre>";
    }
}
