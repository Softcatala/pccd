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

    $paremies = get_db()->query("SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%(o%' OR `MODISME` LIKE '%[o%' ORDER BY `MODISME`")->fetchAll(PDO::FETCH_COLUMN);
    $n = 0;
    $text = '';
    foreach ($paremies as $m) {
        $text .= $m . "\n";
        $n++;
    }
    echo "<p>Total: {$n}</p>";
    echo '<pre>';
    echo $text;
    echo '</pre>';
}
