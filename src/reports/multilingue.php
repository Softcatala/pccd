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

function test_multilingue(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus de la taula RML que no concorda amb cap paremiotipus de la taula 00_PAREMIOTIPUS</h3>';
    echo '<pre>';
    $stmt = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `RML` WHERE `PAREMIOTIPUS` NOT IN (SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`)');
    $paremiotipus = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        // No need to run it through get_paremiotipus_display() as it won't exist there.
        echo $p . "\n";
    }
    echo '</pre>';
}
