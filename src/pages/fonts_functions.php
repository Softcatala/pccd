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
 * Retrieves a list of font records with selected fields from the `00_FONTS` table.
 *
 * @return list<Obra> List of Obra objects with selected fields from the fonts table.
 */
function get_fonts(): array
{
    $stmt = db_prepare('SELECT
        `Any`,
        `Autor`,
        `Identificador`,
        `Registres`,
        `Temàtica`,
        `Títol`,
        `Varietat_dialectal`
    FROM
        `00_FONTS`');
    $stmt->execute();

    $fonts = $stmt->fetchAll(PDO::FETCH_CLASS, Obra::class);

    usort($fonts, static function (Obra $a, Obra $b): int {
        // Remove dots from digits.
        $a = preg_replace('/(?<=\d)\.(?=\d)/', '', $a->Títol);
        $b = preg_replace('/(?<=\d)\.(?=\d)/', '', $b->Títol);
        assert($a !== null && $b !== null);

        // Remove quotes.
        $a = ltrim($a, '"\'«');
        $b = ltrim($b, '"\'«');

        return strnatcasecmp($a, $b);
    });

    return $fonts;
}
