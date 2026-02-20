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

function test_equivalents(): void
{
    require_once __DIR__ . '/../common.php';

    echo "<h3>Modismes amb equivalents amb un codi d'idioma no detectat</h3>";
    echo '<pre>';
    $modismes = get_db()->query('SELECT `MODISME`, `EQUIVALENT`, `IDIOMA` FROM `00_PAREMIOTIPUS` WHERE `EQUIVALENT` IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($modismes as $m) {
        if ($m['IDIOMA'] !== '' && get_idioma($m['IDIOMA']) === '') {
            echo $m['MODISME'] . ' (codi idioma: ' . $m['IDIOMA'] . ', equivalent: ' . $m['EQUIVALENT'] . ")\n";
        }
    }
    echo '</pre>';

    echo '<h3>Modismes amb equivalents amb el camp idioma buit</h3>';
    echo '<details><pre>';
    $modismes = get_db()->query('SELECT `MODISME`, `EQUIVALENT`, `IDIOMA` FROM `00_PAREMIOTIPUS` WHERE `EQUIVALENT` IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($modismes as $modisme) {
        if ($modisme['IDIOMA'] === '' && $modisme['MODISME'] !== '' && $modisme['EQUIVALENT'] !== '') {
            echo $modisme['MODISME'] . ' (equivalent ' . $modisme['EQUIVALENT'] . ")\n";
        }
    }
    echo '</pre></details>';
}
