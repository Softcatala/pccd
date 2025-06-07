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

require __DIR__ . '/../../src/common.php';

$pdo = get_db();

$paremiotipus = $pdo->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
foreach ($paremiotipus as $p) {
    $p_display = get_paremiotipus_display($p, escape_html: false);

    // End the sentence with a dot.
    if (
        !str_ends_with($p_display, '.')
        && !str_ends_with($p_display, '…')
        && !str_ends_with($p_display, '!')
        && !str_ends_with($p_display, '?')
        && !str_ends_with($p_display, ',')
        && !str_ends_with($p_display, ';')
        && !str_ends_with($p_display, ':')
    ) {
        $p_display .= '.';
    }

    fwrite(STDOUT, $p_display . "\n");
}
