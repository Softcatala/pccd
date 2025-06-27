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

    $pdo = get_db();

    echo '<h3>Paremiotipus que comencen o acaben amb espai en blanc</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE CHAR_LENGTH(`PAREMIOTIPUS`) != CHAR_LENGTH(TRIM(`PAREMIOTIPUS`))')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus que contenen 2 espais seguits</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%  %'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus que contenen salts de línia</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%\\n%' OR `PAREMIOTIPUS` LIKE '%\\r%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo trim(get_paremiotipus_display($m, escape_html: false)) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus que contenen el caràcter tabulador</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%\\t%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb espais i parèntesis/claudàtors mal posats</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '% )%' OR `PAREMIOTIPUS` LIKE '%( %' OR `PAREMIOTIPUS` LIKE '% ]%' OR `PAREMIOTIPUS` LIKE '%[ %'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb caràcters invisibles no segurs</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        assert(is_string($m));
        if (preg_match("/\u{200E}/", $m) === 1 || preg_match("/\u{00AD}/", $m) === 1) {
            echo get_paremiotipus_display($m, escape_html: false) . "\n";
        }
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb caràcters sospitosos</h3>';
    echo '<pre>';
    readfile(__DIR__ . '/../../tmp/test_intl_paremiotipus_sospitosos.txt');
    echo '</pre>';

    echo '<h3>Modismes que comencen o acaben amb espai en blanc</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE CHAR_LENGTH(`MODISME`) != CHAR_LENGTH(TRIM(`MODISME`))')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes que contenen 2 espais seguits</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%  %'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes que contenen salts de línia</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%\\n%' OR `MODISME` LIKE '%\\r%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes que contenen el caràcter tabulador</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%\\t%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb espais i parèntesis/claudàtors mal posats</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '% )%' OR `MODISME` LIKE '%( %' OR `MODISME` LIKE '% ]%' OR `MODISME` LIKE '%[ %'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb caràcters invisibles no segurs</h3>';
    echo '<pre>';
    $modismes = $pdo->query('SELECT `MODISME` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        assert(is_string($m));
        if (preg_match("/\u{200E}/", $m) === 1 || preg_match("/\u{00AD}/", $m) === 1) {
            echo $m . "\n";
        }
    }
    echo '</pre>';

    echo '<h3>Modismes amb caràcters sospitosos</h3>';
    echo '<pre>';
    readfile(__DIR__ . '/../../tmp/test_intl_modismes_sospitosos.txt');
    echo '</pre>';

    echo '<h3>Modismes que contenen salts de línia al camp EXPLICACIO</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `EXPLICACIO` LIKE '%\\n%' OR `EXPLICACIO` LIKE '%\\r%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';
}
