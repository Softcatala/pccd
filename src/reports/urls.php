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

function test_urls(): void
{
    echo '<h3>Adreces URL a 00_OBRESVPR.URL que responen un codi HTTP inesperat</h3>';
    echo '<pre>';
    readfile(__DIR__ . '/../../data/reports/test_llibres_url.txt');
    echo '</pre>';

    echo '<h3>Adreces URL a 00_FONTS.URL que responen un codi HTTP inesperat</h3>';
    echo '<pre>';
    readfile(__DIR__ . '/../../data/reports/test_fonts_url.txt');
    echo '</pre>';

    echo '<h3>Adreces URL a 00_IMATGES.URL_IMATGE que responen un codi HTTP inesperat</h3>';
    echo '<details><pre>';
    readfile(__DIR__ . '/../../data/reports/test_imatges_url_imatge.txt');
    echo '</pre></details>';

    echo '<h3>Adreces URL a 00_IMATGES.URL_ENLLAÇ que responen un codi HTTP inesperat</h3>';
    echo '<details><pre>';
    readfile(__DIR__ . '/../../data/reports/test_imatges_url_enllac.txt');
    echo '</pre></details>';
}
