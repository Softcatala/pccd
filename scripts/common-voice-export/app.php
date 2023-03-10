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

declare(strict_types=1);

const CV_MIN_WORDS = 3;
const CV_MAX_WORDS = 14;

/*
 * Export sentences for Common Voice.
 *
 * This script is called by export.sh script.
 */

require __DIR__ . '/../../src/common.php';

require __DIR__ . '/functions.php';

$pdo = get_db();

$cv_res = $pdo->query('SELECT DISTINCT `paremiotipus` FROM `commonvoice`')->fetchAll(PDO::FETCH_COLUMN);
$cv = [];
foreach ($cv_res as $p) {
    $p = standardize_spaces($p);
    $cv[mb_strtolower($p)] = true;
}

$paremiotipus = $pdo->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
foreach ($paremiotipus as $p) {
    $p = standardize_spaces($p);

    // Omit sentences that already exist in Common Voice.
    $p_lowercase = mb_strtolower($p);
    if (isset($cv[$p_lowercase])) {
        continue;
    }

    // Omit sentences that are too short or too long.
    $number_of_words = mb_substr_count($p, ' ') + 1;
    if ($number_of_words < CV_MIN_WORDS || $number_of_words > CV_MAX_WORDS) {
        continue;
    }

    // Omit some sentences that contain inappropriate language.
    if (
        preg_match('/\\bcago\\b/', $p_lowercase) === 1
        || preg_match('/\\bcony\\b/', $p_lowercase) === 1
        || preg_match('/\\bcunyada\\b/', $p_lowercase) === 1
        || preg_match('/\\bcunyades\\b/', $p_lowercase) === 1
        || (preg_match('/\\bdona\\b/', $p_lowercase) === 1 && mb_strpos($p_lowercase, 'dona-') === false)
        || preg_match('/\\bdones\\b/', $p_lowercase) === 1
        || preg_match('/\\bfilla\\b/', $p_lowercase) === 1
        || preg_match('/\\bfilles\\b/', $p_lowercase) === 1
        || preg_match('/\\bgitano\\b/', $p_lowercase) === 1
        || preg_match('/\\bgitanos\\b/', $p_lowercase) === 1
        || preg_match('/\\bmamella\\b/', $p_lowercase) === 1
        || preg_match('/\\bmamelles\\b/', $p_lowercase) === 1
        || preg_match('/\\bmoro\\b/', $p_lowercase) === 1
        || preg_match('/\\bmoros\\b/', $p_lowercase) === 1
        || preg_match('/\\bmuller\\b/', $p_lowercase) === 1
        || preg_match('/\\bputa\\b/', $p_lowercase) === 1
        || preg_match('/\\bputes\\b/', $p_lowercase) === 1
        || preg_match('/\\bsogra\\b/', $p_lowercase) === 1
        || preg_match('/\\bsogres\\b/', $p_lowercase) === 1
    ) {
        continue;
    }

    echo $p;
    if (preg_match('/[.!?,;:]$/', $p) === 0) {
        echo '.';
    }
    echo \PHP_EOL;
}
