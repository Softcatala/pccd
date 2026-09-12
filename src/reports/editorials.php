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

    $editorials_modismes = db_query('SELECT DISTINCT `EDITORIAL`, 1 FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_KEY_PAIR);

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
    $editorials_paremiotipus = db_query('SELECT `MODISME`, `EDITORIAL` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_ASSOC);

    echo '<h3>Editorials que estan referenciades per parèmies, però que no existeixen a la taula 00_EDITORIA</h3>';
    $output = '';
    foreach ($editorials_paremiotipus as $ed_p) {
        assert(is_string($ed_p['EDITORIAL']));
        if ($ed_p['EDITORIAL'] !== '' && !isset($editorials[$ed_p['EDITORIAL']])) {
            $output .= $ed_p['EDITORIAL'] . ' (' . $ed_p['MODISME'] . ")\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}
