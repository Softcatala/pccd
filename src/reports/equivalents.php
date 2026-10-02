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
    $stmt = db_query('SELECT `MODISME`, `EQUIVALENT`, `IDIOMA` FROM `00_PAREMIOTIPUS` WHERE `EQUIVALENT` IS NOT NULL');
    $output = '';
    while (($result = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
        assert(is_string($result['MODISME']));
        assert(is_string($result['EQUIVALENT']));
        assert(is_string($result['IDIOMA']));
        if ($result['IDIOMA'] !== '' && get_idioma($result['IDIOMA']) === '') {
            $output .= $result['MODISME'] . ' (codi idioma: ' . $result['IDIOMA'] . ', equivalent: ' . $result['EQUIVALENT'] . ")\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes amb equivalents amb el camp idioma buit</h3>';
    $stmt = db_query('SELECT `MODISME`, `EQUIVALENT`, `IDIOMA` FROM `00_PAREMIOTIPUS` WHERE `EQUIVALENT` IS NOT NULL');
    $output = '';
    while (($result = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
        assert(is_string($result['MODISME']));
        assert(is_string($result['EQUIVALENT']));
        assert(is_string($result['IDIOMA']));
        if ($result['IDIOMA'] === '' && $result['MODISME'] !== '' && $result['EQUIVALENT'] !== '') {
            $output .= $result['MODISME'] . ' (equivalent ' . $result['EQUIVALENT'] . ")\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }
}
