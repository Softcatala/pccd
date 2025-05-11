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

// Export sentences for Common Voice.
// This script outputs potentially controversial sentences to stderr, and the
// other ones to stdout.

const CV_MIN_WORDS = 3;
const CV_MAX_WORDS = 14;

require __DIR__ . '/../../src/common.php';

require __DIR__ . '/functions.php';

// Words that may indicate inappropriate content.
$inappropriate_words = [
    'cago', 'cony', 'cunyada', 'cunyades', 'dona', 'dones', 'filla', 'filles',
    'gitano', 'gitanos', 'mamella', 'mamelles', 'moro', 'moros', 'muller',
    'puta', 'putes', 'sogra', 'sogres',
];

// Valid ending punctuation.
$valid_endings = ['.', '…', '!', '?', ',', ';', ':'];

$paremiotipus = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
foreach ($paremiotipus as $p) {
    assert(is_string($p));

    // Skip sentences that are too short or too long.
    $number_of_words = mb_substr_count($p, ' ') + 1;
    if ($number_of_words < CV_MIN_WORDS) {
        continue;
    }
    if ($number_of_words > CV_MAX_WORDS) {
        continue;
    }

    $p_display = get_paremiotipus_display($p, escape_html: false);

    // Add a period if the sentence doesn't end with punctuation.
    $needs_period = true;
    foreach ($valid_endings as $ending) {
        if (str_ends_with($p_display, $ending)) {
            $needs_period = false;

            break;
        }
    }

    if ($needs_period) {
        $p_display .= '.';
    }

    // Check for inappropriate content.
    $p_lowercase = mb_strtolower($p);
    $is_inappropriate = false;
    foreach ($inappropriate_words as $word) {
        // Special case for "dona" which is allowed in conjugations of "donar".
        if ($word === 'dona' && str_contains($p_lowercase, 'dona-')) {
            continue;
        }

        if (preg_match('/\b' . $word . '\b/', $p_lowercase) === 1) {
            $is_inappropriate = true;

            break;
        }
    }

    if ($is_inappropriate) {
        fwrite(STDERR, $p_display . "\n");

        continue;
    }

    // Process and output clean sentences.
    $p_display = remove_parentheses($p_display);
    if ($p_display === '') {
        continue;
    }

    fwrite(STDOUT, $p_display . "\n");
}
