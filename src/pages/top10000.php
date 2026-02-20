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

// This page is currently not discoverable.
header('X-Robots-Tag: noindex');

PageRenderer::setTitle('Les 10.000 parèmies més citades');

$stmt = get_db()->query('SELECT `Paremiotipus` FROM `common_paremiotipus` ORDER BY `Compt` DESC');
$records = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo '<ol>';
foreach ($records as $paremiotipus_id) {
    echo '<li><a href="' . get_paremiotipus_url($paremiotipus_id) . '">' . get_paremiotipus_display($paremiotipus_id) . '</a></li>';
}
echo '</ol>';
