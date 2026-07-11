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

function test_fonts_any_erroni(): void
{
    require_once __DIR__ . '/../common.php';

    echo "<h3>Obres amb l'any probablement incorrecte</h3>";
    $stmt = db_query('SELECT `Identificador`, `Any` FROM `00_FONTS`');
    $imatges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $output = '';
    foreach ($imatges as $i) {
        if (((int) $i['Any']) < 0 || ((int) $i['Any']) > (int) date('Y')) {
            $output .= $i['Identificador'] . ' (' . $i['Any'] . ")\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo "<h3>Obres amb l'any d'edició probablement incorrecte</h3>";
    $stmt = db_query('SELECT `Identificador`, `Any_edició` FROM `00_FONTS`');
    $imatges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $output = '';
    foreach ($imatges as $imatge) {
        if (((int) $imatge['Any_edició']) < 0 || ((int) $imatge['Any_edició']) > (int) date('Y')) {
            $output .= $imatge['Identificador'] . ' (' . $imatge['Any_edició'] . ")\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}

function test_paremies_any_erroni(): void
{
    require_once __DIR__ . '/../common.php';

    echo "<h3>Modismes amb l'any probablement incorrecte</h3>";
    $stmt = db_query('SELECT `MODISME`, `Any` FROM `00_PAREMIOTIPUS`');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $output = '';
    foreach ($results as $result) {
        if (((int) $result['Any']) < 0 || ((int) $result['Any']) > (int) date('Y')) {
            $output .= $result['MODISME'] . ' (' . $result['Any'] . ")\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}

function test_imatges_any_erroni(): void
{
    require_once __DIR__ . '/../common.php';

    echo "<h3>Imatges amb l'any probablement incorrecte</h3>";
    $stmt = db_query('SELECT `Identificador`, `Any` FROM `00_IMATGES`');
    $imatges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $output = '';
    foreach ($imatges as $imatge) {
        if (((int) $imatge['Any']) < 0 || ((int) $imatge['Any']) > (int) date('Y')) {
            $output .= 'paremies/' . $imatge['Identificador'] . ' (' . $imatge['Any'] . ")\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}
