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
    $modismes = db_query('SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE CHAR_LENGTH(`PAREMIOTIPUS`) != CHAR_LENGTH(TRIM(`PAREMIOTIPUS`))')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus que contenen 2 espais seguits</h3>';
    $modismes = db_query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%  %'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus que contenen salts de línia</h3>';
    $modismes = db_query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%\\n%' OR `PAREMIOTIPUS` LIKE '%\\r%'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= trim(get_paremiotipus_display($m, escape_html: false)) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus que comencen o acaben amb caràcters invisibles</h3>';
    $modismes = db_query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        assert(is_string($m));
        if (trim($m) !== mb_trim($m)) {
            $output .= get_paremiotipus_display($m, escape_html: false) . "\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus que contenen el caràcter tabulador</h3>';
    $modismes = db_query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%\\t%'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus amb espais i parèntesis/claudàtors mal posats</h3>';
    $modismes = db_query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '% )%' OR `PAREMIOTIPUS` LIKE '%( %' OR `PAREMIOTIPUS` LIKE '% ]%' OR `PAREMIOTIPUS` LIKE '%[ %'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus amb caràcters invisibles no segurs</h3>';
    $modismes = db_query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        assert(is_string($m));
        if (str_contains($m, "\u{200E}") || str_contains($m, "\u{00AD}")) {
            $output .= get_paremiotipus_display($m, escape_html: false) . "\n";
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
        assert(is_string($m));
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
        assert(is_string($m));
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
