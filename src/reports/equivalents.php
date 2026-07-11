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
    $modismes = db_query('SELECT `MODISME`, `EQUIVALENT`, `IDIOMA` FROM `00_PAREMIOTIPUS` WHERE `EQUIVALENT` IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
    $output = '';
    foreach ($modismes as $m) {
        if ($m['IDIOMA'] !== '' && get_idioma($m['IDIOMA']) === '') {
            $output .= $m['MODISME'] . ' (codi idioma: ' . $m['IDIOMA'] . ', equivalent: ' . $m['EQUIVALENT'] . ")\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes amb equivalents amb el camp idioma buit</h3>';
    $modismes = db_query('SELECT `MODISME`, `EQUIVALENT`, `IDIOMA` FROM `00_PAREMIOTIPUS` WHERE `EQUIVALENT` IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
    $output = '';
    foreach ($modismes as $modisme) {
        if ($modisme['IDIOMA'] === '' && $modisme['MODISME'] !== '' && $modisme['EQUIVALENT'] !== '') {
            $output .= $modisme['MODISME'] . ' (equivalent ' . $modisme['EQUIVALENT'] . ")\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }
}
