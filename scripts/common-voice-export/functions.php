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
function standardize_spaces(string $input_string): string
{
    $string = str_replace(["\n", "\r", "\t"], ' ', $input_string);
    $string = preg_replace('/\s+/', ' ', $string);
    assert(is_string($string));

    return trim($string);
}

/**
 * Remove parentheses and everything inside them from a given string.
 */
function remove_parentheses(string $input_string): string
{
    // Handle specific examples such as "A (o d')aquesta part".
    $string = str_replace(')a', ') a', $input_string);

    return preg_replace('/\s*\([^)]*\)/', '', $string) ?? '';
}
