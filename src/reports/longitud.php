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

function test_buits(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Modismes amb el camp PAREMIOTIPUS buit</h3>';
    $modismes = db_query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` where `PAREMIOTIPUS` = '' OR `PAREMIOTIPUS` IS NULL")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus amb el camp MODISME buit</h3>';
    $modismes = db_query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` where `MODISME` = '' OR `MODISME` IS NULL")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}

function test_paremiotipus_llargs(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus de més de 250 caràcters</h3>';
    $modismes = db_query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE CHAR_LENGTH(`PAREMIOTIPUS`) > 250 ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }
}

function test_paremiotipus_modismes_curts(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus de menys de 5 caràcters</h3>';
    $paremiotipus = db_query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE CHAR_LENGTH(`PAREMIOTIPUS`) < 5 ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $existing = [];
    $output = '';
    foreach ($paremiotipus as $m) {
        $existing[$m] = true;
        $output .= get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }

    echo '<h3>Modismes de menys de 5 caràcters (no repetits a la llista anterior)</h3>';
    $modismes = db_query('SELECT DISTINCT `MODISME` as `MODISME` FROM `00_PAREMIOTIPUS` WHERE CHAR_LENGTH(`MODISME`) < 5 ORDER BY `MODISME`')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $modisme) {
        assert(is_string($modisme));
        if (!isset($existing[$modisme])) {
            $output .= $modisme . "\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }
}

function test_explicacions_curtes(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Registres de la taula 00_PAREMIOTIPUS amb menys de 4 caràcters al camp EXPLICACIO</h3>';
    $modismes = db_query('SELECT DISTINCT `MODISME`, `EXPLICACIO` FROM `00_PAREMIOTIPUS` WHERE `EXPLICACIO` IS NOT NULL AND CHAR_LENGTH(`EXPLICACIO`) < 4 ORDER BY `MODISME`')->fetchAll(PDO::FETCH_KEY_PAIR);
    $output = '';
    foreach ($modismes as $key => $value) {
        $output .= $value . " (modisme: {$key})\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }
}

function test_fonts_curtes(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Registres de la taula 00_PAREMIOTIPUS amb menys de 2 caràcters al camp FONT</h3>';
    $modismes = db_query('SELECT DISTINCT `MODISME`, `FONT` FROM `00_PAREMIOTIPUS` WHERE `FONT` IS NOT NULL AND CHAR_LENGTH(`FONT`) < 2 ORDER BY `MODISME`')->fetchAll(PDO::FETCH_KEY_PAIR);
    $output = '';
    foreach ($modismes as $key => $value) {
        $output .= $value . " (modisme: {$key})\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }
}
