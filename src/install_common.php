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
 * Checks if a table exists in the database.
 */
function table_exists(string $table): bool
{
    $pdo = get_db();

    // Try a select statement against the table.
    // Run it in try/catch in case PDO is in ERRMODE_EXCEPTION.
    try {
        $result = $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
    } catch (Exception) {
        // If we got an exception it means that the table is not found.
        return false;
    }

    // $result is either false or PDOStatement Object.
    return $result !== false;
}

/**
 * Store image width and height in the database.
 */
function store_image_dimensions(string $table, string $field, string $directory): void
{
    $pdo = get_db();

    $update_stmt = $pdo->prepare("UPDATE `{$table}` SET `WIDTH` = ?, `HEIGHT` = ? WHERE `{$field}` = ?");

    $images = $pdo->query("SELECT `{$field}` FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($images as $image) {
        $filename = __DIR__ . '/../' . $directory . '/' . $image;
        if (!is_file($filename)) {
            continue;
        }

        $image_size = getimagesize($filename);
        if ($image_size === false) {
            continue;
        }

        [$width, $height] = $image_size;
        if ($width < 1) {
            continue;
        }

        $update_stmt->execute([$width, $height, $image]);
    }
}

/**
 * Returns a paremiotipus formatted for improved sorting, removing useless characters.
 */
function clean_paremiotipus_for_sorting(string $input_paremiotipus): string
{
    $paremiotipus = str_replace(['(', ')', '«', '»'], '', $input_paremiotipus);

    return ltrim($paremiotipus, "º-–—―─'\"“”‘’….¡¿* \n\r\t\v\0\x95");
}
