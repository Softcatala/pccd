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
    echo '<h3>Valors de 00_OBRESVPR.URL que responen diferent de HTTP 200/301/302/307/308</h3>';
    echo '<pre>';
    readfile(__DIR__ . '/../../tmp/test_llibres_URL.txt');
    echo '</pre>';

    echo '<h3>Valors de 00_FONTS.URL que responen diferent de HTTP 200/301/302/307/308</h3>';
    echo '<pre>';
    readfile(__DIR__ . '/../../tmp/test_fonts_URL.txt');
    echo '</pre>';

    echo '<h3>Valors de 00_IMATGES.URL_IMATGE que responen diferent de HTTP 200/301/302/307/308</h3>';
    echo '<details><pre>';
    readfile(__DIR__ . '/../../tmp/test_imatges_URL_IMATGE.txt');
    echo '</pre></details>';

    echo '<h3>Valors de 00_IMATGES.URL_ENLLAÇ que responen diferent de HTTP 200/301/302/307/308</h3>';
    echo '<details><pre>';
    readfile(__DIR__ . '/../../tmp/test_imatges_URL_ENLLAC.txt');
    echo '</pre></details>';
}
