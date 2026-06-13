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

/*
 * Prints the sitemap contents to stdout.
 *
 * This file is called by install.sh script.
 */

require __DIR__ . '/../../src/common.php';

$site = BASE_URL;

$urls = $site . "\n";

$discoverable_static_pages = array_diff(PageRenderer::STATIC_PAGE_NAMES, ['top10000']);
foreach ($discoverable_static_pages as $page) {
    $urls .= "{$site}/{$page}\n";
}

$stmt = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`');
$paremiotipus = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($paremiotipus as $p) {
    $urls .= get_paremiotipus_url($p, absolute: true) . "\n";
}

$stmt = get_db()->query('SELECT DISTINCT `Identificador` FROM `00_FONTS`');
$obres = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($obres as $o) {
    $urls .= get_obra_url($o, absolute: true) . "\n";
}

echo $urls;
