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

function test_majuscules(): void
{
    require_once __DIR__ . '/../common.php';
    echo '<h3>Paremiotipus que comencen amb lletra minúscula</h3>';
    $modismes = db_query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        assert(is_string($m));
        if (mb_ucfirst($m) !== $m) {
            $display = get_paremiotipus_display($m);
            if (mb_ucfirst($display) !== $display) {
                $output .= $display . "\n";
            }
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo "<h3>Paremiotipus que tenen una lletra minúscula seguida d'una lletra majúscula</h3>";
    $modismes = db_query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` REGEXP BINARY '[a-z]+[A-Z]+'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus que tenen dues lletres majúscules seguides</h3>';
    $modismes = db_query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` REGEXP BINARY '[A-Z]+[A-Z]+'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Paremiotipus que acaben amb lletra majúscula</h3>';
    $modismes = db_query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE BINARY LOWER(SUBSTRING(`PAREMIOTIPUS`, -1)) != SUBSTRING(`PAREMIOTIPUS`, -1)')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= get_paremiotipus_display($m, escape_html: false) . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes que comencen amb lletra minúscula</h3>';
    $modismes = db_query('SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        assert(is_string($m));
        if (mb_ucfirst($m) !== $m) {
            $output .= $m . "\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo "<h3>Modismes que tenen una lletra minúscula seguida d'una lletra majúscula</h3>";
    $modismes = db_query("SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` REGEXP BINARY '[a-z]+[A-Z]+'")->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($modismes as $m) {
        $output .= $m . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes que acaben amb lletra majúscula</h3>';
    $modismes = db_query('SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS` WHERE BINARY LOWER(SUBSTRING(`MODISME`, -1)) != SUBSTRING(`MODISME`, -1)')->fetchAll(PDO::FETCH_COLUMN);
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
