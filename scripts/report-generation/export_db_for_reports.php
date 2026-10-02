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

use Pdo\Mysql;

require __DIR__ . '/../../src/common.php';

require __DIR__ . '/export_helpers.php';

$database = get_db();
$database->setAttribute(Mysql::ATTR_USE_BUFFERED_QUERY, false);

$output_directory = __DIR__ . '/../../tmp';
if (!is_dir($output_directory) && !mkdir($output_directory, recursive: true) && !is_dir($output_directory)) {
    throw new RuntimeException("Failed to create {$output_directory}");
}

write_report_export_json(
    'report_paremiotipus.json',
    db_query('SELECT
    `paremiotipus`.`PAREMIOTIPUS` AS `value`,
    COALESCE(NULLIF(`display`.`Display`, \'\'), `paremiotipus`.`PAREMIOTIPUS`) AS `display`
FROM
    (SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`) `paremiotipus`
LEFT JOIN
    `paremiotipus_display` `display`
ON
    `display`.`Paremiotipus` = `paremiotipus`.`PAREMIOTIPUS`
ORDER BY
    `paremiotipus`.`PAREMIOTIPUS`'),
    $output_directory
);

write_report_export_json(
    'report_modismes.json',
    db_query('SELECT DISTINCT
    `paremiotipus`.`MODISME`,
    `paremiotipus`.`PAREMIOTIPUS`,
    COALESCE(NULLIF(`display`.`Display`, \'\'), `paremiotipus`.`PAREMIOTIPUS`) AS `Display`
FROM
    `00_PAREMIOTIPUS` `paremiotipus`
LEFT JOIN
    `paremiotipus_display` `display`
ON
    `display`.`Paremiotipus` = `paremiotipus`.`PAREMIOTIPUS`
ORDER BY
    `paremiotipus`.`PAREMIOTIPUS`,
    `paremiotipus`.`MODISME`'),
    $output_directory
);

write_report_export_json(
    'report_modismes_sospitosos.json',
    db_query('SELECT `MODISME` FROM `00_PAREMIOTIPUS`'),
    $output_directory,
    PDO::FETCH_COLUMN
);

write_report_export_json(
    'report_fonts.json',
    db_query('SELECT `URL`, `Identificador`, `Imatge` FROM `00_FONTS`'),
    $output_directory
);

write_report_export_json(
    'report_llibres.json',
    db_query('SELECT `URL`, `Títol`, `Imatge` FROM `00_OBRESVPR`'),
    $output_directory
);

write_report_export_json(
    'report_imatges.json',
    db_query('SELECT `Identificador`, `URL_IMATGE`, `URL_ENLLAÇ` FROM `00_IMATGES`'),
    $output_directory
);

write_report_export_json(
    'report_paremiotipus_accents.json',
    db_query('SELECT DISTINCT BINARY
    `paremiotipus`.`PAREMIOTIPUS`
FROM
    `00_PAREMIOTIPUS` `paremiotipus`
INNER JOIN (
    SELECT `PAREMIOTIPUS`
    FROM `00_PAREMIOTIPUS`
    GROUP BY `PAREMIOTIPUS`
    HAVING COUNT(DISTINCT BINARY `PAREMIOTIPUS`) > 1
) `variants`
ON
    `variants`.`PAREMIOTIPUS` = `paremiotipus`.`PAREMIOTIPUS`'),
    $output_directory,
    PDO::FETCH_COLUMN
);
