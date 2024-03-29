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

/**
 * Standardize spaces in a string.
 */
function standardize_spaces(string $string): string
{
    $string = str_replace(["\n", "\r", "\t"], ' ', $string);
    $string = preg_replace('/\s+/', ' ', $string);
    if (!is_string($string)) {
        return '';
    }

    return trim($string);
}
