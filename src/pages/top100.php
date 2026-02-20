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

// Set page metadata.
PageRenderer::setTitle('Les 100 parèmies més citades');
PageRenderer::setMetaDescription('Llista de les frases més citades de la Paremiologia catalana comparada digital.');

$stmt = get_db()->query('SELECT `Paremiotipus` FROM `common_paremiotipus` ORDER BY `Compt` DESC LIMIT 100');
$records = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo '<ol>';
foreach ($records as $paremiotipus_id) {
    echo '<li><a href="' . get_paremiotipus_url($paremiotipus_id) . '">' . get_paremiotipus_display($paremiotipus_id) . '</a></li>';
}
echo '</ol>';
