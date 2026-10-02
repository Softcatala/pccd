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

function test_editorials_no_referenciades(): void
{
    require_once __DIR__ . '/../common.php';

    $stmt = db_query('SELECT DISTINCT `EDITORIAL`, 1 FROM `00_PAREMIOTIPUS`');
    $editorials_modismes = [];
    while (($result = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
        assert(is_string($result[0]));
        $editorials_modismes[$result[0]] = $result[1];
    }

    echo '<h3>Editorials de la taula 00_EDITORIA que no estan referenciades per cap paremiotipus</h3>';
    $output = '';
    foreach (get_editorials() as $ed_codi => $ed_title) {
        if (!isset($editorials_modismes[$ed_codi])) {
            $output .= "{$ed_codi}: {$ed_title}\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }
}

function test_editorials_no_existents(): void
{
    require_once __DIR__ . '/../common.php';

    $editorials = get_editorials();
    $stmt = db_query('SELECT `MODISME`, `EDITORIAL` FROM `00_PAREMIOTIPUS`');

    echo '<h3>Editorials que estan referenciades per parèmies, però que no existeixen a la taula 00_EDITORIA</h3>';
    $output = '';
    while (($result = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
        assert(is_string($result['MODISME']));
        assert(is_string($result['EDITORIAL']));
        if ($result['EDITORIAL'] !== '' && !isset($editorials[$result['EDITORIAL']])) {
            $output .= $result['EDITORIAL'] . ' (' . $result['MODISME'] . ")\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}
