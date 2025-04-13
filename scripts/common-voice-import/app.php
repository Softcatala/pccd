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

/*
 * Import Common Voice sentences.
 *
 * This script outputs the mv commands to stderr, and the SQL output to stdout.
 */

ini_set('memory_limit', '1024M');

require __DIR__ . '/../../src/common.php';

const CV_DIR = '../../tmp/cv/cv-corpus-14.0-2023-06-23/ca';

$pdo = get_db();

// Read the TSV file amd split it into lines.
$lines = file(__DIR__ . '/' . CV_DIR . '/validated.tsv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
assert(is_array($lines));

$modismes_paremiotipus = $pdo->query('SELECT DISTINCT `MODISME`, `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`, `MODISME`')->fetchAll(PDO::FETCH_KEY_PAIR);
$paremiotipus_array = $pdo->query('SELECT DISTINCT `PAREMIOTIPUS`, 1 FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_KEY_PAIR);
$paremiotipus_display_array = $pdo->query('SELECT `Display`, `Paremiotipus` FROM `paremiotipus_display` ORDER BY `Paremiotipus`, `Display`')->fetchAll(PDO::FETCH_KEY_PAIR);

$sentences_array = [];
foreach ($lines as $i => $line) {
    if ($i === 0) {
        continue;
    }

    [, $path, $sentence, $up_votes, $down_votes] = explode("\t", $line);
    $sentence = trim($sentence, ". \n\r\t\v\x00");
    $votes = (int) $up_votes - (int) $down_votes;

    // Discard pronunciations with less than 1 positive votes.
    if ($votes < 1) {
        continue;
    }

    $paremiotipus = '';
    if (array_key_exists($sentence, $paremiotipus_array)) {
        $paremiotipus = $sentence;
    } elseif (array_key_exists($sentence, $paremiotipus_display_array)) {
        $paremiotipus = $paremiotipus_display_array[$sentence];
    } elseif (array_key_exists($sentence, $modismes_paremiotipus)) {
        $paremiotipus = $modismes_paremiotipus[$sentence];
    }

    if ($paremiotipus === '') {
        continue;
    }

    if (!array_key_exists($paremiotipus, $sentences_array)) {
        $sentences_array[$paremiotipus] = [];
    }

    $sentences_array[$paremiotipus][] = [
        'f' => $path,
        'v' => $votes,
    ];
}

$cp_commands = '';
$sql = 'DROP TABLE IF EXISTS `commonvoice`;
        CREATE TABLE `commonvoice` (
            `paremiotipus` varchar(255) NOT NULL,
            `file` varchar(200) NOT NULL,
            PRIMARY KEY (`paremiotipus`, `file`)
        );' . "\n";
$sql .= 'INSERT IGNORE INTO `commonvoice`(`paremiotipus`, `file`) VALUES ' . "\n";

foreach ($sentences_array as $paremiotipus => $pronunciations) {
    // Sort by votes and keep the top 20.
    usort($pronunciations, static fn (array $a, array $b): int => $b['v'] <=> $a['v']);
    $pronunciations = array_slice($pronunciations, 0, 20);

    assert(is_string($paremiotipus));
    $paremiotipus = str_replace("'", "''", $paremiotipus);
    foreach ($pronunciations as $pronunciation) {
        $file = $pronunciation['f'];
        $sql .= "('{$paremiotipus}', '{$file}'),";
        $cp_commands .= 'cp ' . CV_DIR . "/clips/{$file} ../../docroot/mp3/{$file}\n";
    }
}

fwrite(STDERR, $cp_commands);
fwrite(STDOUT, rtrim($sql, ',') . ';' . "\n");
