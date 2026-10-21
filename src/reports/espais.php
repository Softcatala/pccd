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

function test_espais(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus que comencen o acaben amb espai en blanc</h3>';
    $paremiotipus = db_query('SELECT `Display` FROM `paremiotipus_display` WHERE CHAR_LENGTH(`Display`) != CHAR_LENGTH(TRIM(`Display`))')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= $p . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus que contenen 2 espais seguits</h3>';
    $paremiotipus = db_query("SELECT `Display` FROM `paremiotipus_display` WHERE `Display` LIKE '%  %'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= $p . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus que contenen salts de línia</h3>';
    $paremiotipus = db_query("SELECT `Display` FROM `paremiotipus_display` WHERE `Display` LIKE '%\\n%' OR `Display` LIKE '%\\r%'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= trim($p) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus que comencen o acaben amb caràcters invisibles</h3>';
    $paremiotipus = db_query('SELECT `Display` FROM `paremiotipus_display`')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        if (trim($p) !== mb_trim($p)) {
            $output .= $p . "\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus que contenen el caràcter tabulador</h3>';
    $paremiotipus = db_query("SELECT `Display` FROM `paremiotipus_display` WHERE `Display` LIKE '%\\t%'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= $p . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus amb espais i parèntesis/claudàtors mal posats</h3>';
    $paremiotipus = db_query("SELECT `Display` FROM `paremiotipus_display` WHERE `Display` LIKE '% )%' OR `Display` LIKE '%( %' OR `Display` LIKE '% ]%' OR `Display` LIKE '%[ %'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= $p . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus amb caràcters invisibles no segurs</h3>';
    $paremiotipus = db_query('SELECT `Display` FROM `paremiotipus_display`')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        if (str_contains($p, "\u{200E}") || str_contains($p, "\u{00AD}")) {
            $output .= $p . "\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus amb caràcters sospitosos</h3>';
    $output = trim((string) @file_get_contents(__DIR__ . '/../../data/reports/test_intl_paremiotipus_sospitosos.txt'));
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes que comencen o acaben amb espai en blanc</h3>';
    $modismes = db_query('SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE CHAR_LENGTH(`MODISME`) != CHAR_LENGTH(TRIM(`MODISME`))')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes que contenen 2 espais seguits</h3>';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%  %'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes que contenen salts de línia</h3>';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%\\n%' OR `MODISME` LIKE '%\\r%'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes que comencen o acaben amb caràcters invisibles</h3>';
    $modismes = db_query('SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        if (trim($m) !== mb_trim($m)) {
            $output .= $m . "\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes que contenen el caràcter tabulador</h3>';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%\\t%'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes amb espais i parèntesis/claudàtors mal posats</h3>';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '% )%' OR `MODISME` LIKE '%( %' OR `MODISME` LIKE '% ]%' OR `MODISME` LIKE '%[ %'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes amb caràcters invisibles no segurs</h3>';
    $modismes = db_query('SELECT `MODISME` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        if (str_contains($m, "\u{200E}") || str_contains($m, "\u{00AD}")) {
            $output .= $m . "\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes amb caràcters sospitosos</h3>';
    $output = trim((string) @file_get_contents(__DIR__ . '/../../data/reports/test_intl_modismes_sospitosos.txt'));
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes que contenen salts de línia al camp EXPLICACIO</h3>';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `EXPLICACIO` LIKE '%\\n%' OR `EXPLICACIO` LIKE '%\\r%'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}
