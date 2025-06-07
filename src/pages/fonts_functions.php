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
 * @return list<Obra>
 */
function get_fonts(): array
{
    $stmt = get_db()->prepare('SELECT
        `Any`,
        `Autor`,
        `Identificador`,
        `Registres`,
        `Títol`,
        `Varietat_dialectal`
    FROM
        `00_FONTS`');
    $stmt->execute();

    /** @var list<Obra> */
    return $stmt->fetchAll(PDO::FETCH_CLASS, Obra::class);
}
