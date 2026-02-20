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
 * Retrieves an Obra object by its title/identifier.
 *
 * The obra_title parameter specifies the identifier or title of the Obra to retrieve.
 *
 * @return false|Obra The Obra object if found, or false if not found.
 */
function get_obra(string $obra_title): false|Obra
{
    $stmt = get_db()->prepare('SELECT
        `Any_edició`,
        `Any`,
        `Autor`,
        `Collecció`,
        `Data_compra`,
        `Edició`,
        `Editorial`,
        `HEIGHT`,
        `ISBN`,
        `Identificador`,
        `Idioma`,
        `Imatge`,
        `Lloc_compra`,
        `Municipi`,
        `Núm_collecció`,
        `Observacions`,
        `Preu`,
        `Pàgines`,
        `Registres`,
        `Títol`,
        `URL`,
        `Varietat_dialectal`,
        `WIDTH`
    FROM
        `00_FONTS`
    WHERE
        `Identificador` = :id');
    $stmt->execute([':id' => $obra_title]);

    return $stmt->fetchObject(Obra::class);
}

/**
 * ISBN simple (but incorrect) validation.
 *
 * The input_isbn parameter specifies the ISBN string to validate.
 */
function isbn_is_valid(string $input_isbn): bool
{
    $isbn = str_replace('-', '', $input_isbn);
    $isbn_removed_chars = preg_replace('/[^a-zA-Z0-9]/', '', $isbn);

    return $isbn === $isbn_removed_chars && (strlen($isbn) === 10 || strlen($isbn) === 13);
}

/**
 * Returns the number of paremiotipus for a specific font.
 *
 * The font_id parameter specifies the font identifier to count paremiotipus for.
 */
function get_paremiotipus_count_by_font(string $font_id): int
{
    $stmt = get_db()->prepare('SELECT COUNT(1) FROM `00_PAREMIOTIPUS` WHERE `ID_FONT` = :id');
    $stmt->execute([':id' => $font_id]);

    return (int) $stmt->fetchColumn();
}

/**
 * Formats a price in Catalan.
 *
 * The input_price parameter specifies the price string to format.
 */
function format_preu(string $input_price): string
{
    $num = (float) $input_price;
    $decimals = $num === floor($num) ? 0 : 2;

    return number_format(
        $num,
        decimals: $decimals,
        decimal_separator: ',',
        thousands_separator: '.'
    );
}
