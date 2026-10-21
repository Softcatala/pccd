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
    $paremiotipus = db_query("SELECT `d`.`Display` FROM `00_PAREMIOTIPUS` `p` JOIN `paremiotipus_display` `d` ON `d`.`Paremiotipus` = `p`.`PAREMIOTIPUS` where `p`.`MODISME` = '' OR `p`.`MODISME` IS NULL")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= $p . "\n";
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
    $paremiotipus = db_query('SELECT `Display` FROM `paremiotipus_display` WHERE CHAR_LENGTH(`Display`) > 250 ORDER BY `Display`')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= $p . "\n";
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
    $rows = db_query('SELECT `p`.`PAREMIOTIPUS`, `d`.`Display` FROM `00_PAREMIOTIPUS` `p` JOIN `paremiotipus_display` `d` ON `d`.`Paremiotipus` = `p`.`PAREMIOTIPUS` WHERE CHAR_LENGTH(`d`.`Display`) < 5 ORDER BY `p`.`PAREMIOTIPUS`')->fetchAll(PDO::FETCH_ASSOC);
    $existing = [];
    $output = '';
    foreach ($rows as $row) {
        assert(is_string($row['PAREMIOTIPUS']));
        assert(is_string($row['Display']));
        $existing[$row['PAREMIOTIPUS']] = true;
        $output .= $row['Display'] . "\n";
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
