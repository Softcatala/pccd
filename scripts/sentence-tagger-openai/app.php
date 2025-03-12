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

require __DIR__ . '/../../vendor/autoload.php';

require __DIR__ . '/../../src/common.php';

require __DIR__ . '/functions.php';

$file_path = __DIR__ . '/../../tmp/tags_output.json';

$paremiotipus = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
foreach ($paremiotipus as $sentence) {
    $display = get_paremiotipus_display($sentence, escape_html: false);
    if (str_contains($display, ' ') && mb_strlen($display) > 10) {
        append_to_json_file(['sentence' => $sentence, 'tags' => get_sentence_tags($display)], $file_path);
    }
}
