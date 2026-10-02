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

require __DIR__ . '/../../../src/common.php';

$database = get_db();
$database->setAttribute(Mysql::ATTR_USE_BUFFERED_QUERY, false);

$statement = db_query('SELECT
    COALESCE(NULLIF(`display`.`Display`, \'\'), `paremiotipus`.`PAREMIOTIPUS`) AS `display`,
    `paremiotipus`.`PAREMIOTIPUS` AS `value`
FROM
    (SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`) `paremiotipus`
LEFT JOIN
    `paremiotipus_display` `display`
ON
    `display`.`Paremiotipus` = `paremiotipus`.`PAREMIOTIPUS`
ORDER BY
    `paremiotipus`.`PAREMIOTIPUS`');

while (($p_display = $statement->fetchColumn()) !== false) {
    assert(is_string($p_display));

    // End the sentence with a dot.
    if (
        !str_ends_with($p_display, '.')
        && !str_ends_with($p_display, '…')
        && !str_ends_with($p_display, '!')
        && !str_ends_with($p_display, '?')
        && !str_ends_with($p_display, ',')
        && !str_ends_with($p_display, ';')
        && !str_ends_with($p_display, ':')
    ) {
        $p_display .= '.';
    }

    fwrite(STDOUT, $p_display . "\n");
}

$statement->closeCursor();
