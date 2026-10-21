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

    $current_year = (int) date('Y');
    $fonts = db_query('SELECT `Identificador`, `Any`, `Any_edició` FROM `00_FONTS`')->fetchAll(PDO::FETCH_NUM);
    $year_output = '';
    $edition_year_output = '';
    foreach ($fonts as $font) {
        [$id, $year, $edition_year] = $font;
        assert(is_string($id));
        $year = (int) $year;
        $edition_year = (int) $edition_year;
        if ($year < 0 || $year > $current_year) {
            $year_output .= $id . ' (' . $year . ")\n";
        }

        if ($edition_year < 0 || $edition_year > $current_year) {
            $edition_year_output .= $id . ' (' . $edition_year . ")\n";
        }
    }

    echo "<h3>Obres amb l'any probablement incorrecte</h3>";
    if ($year_output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$year_output}</pre>";
    }

    echo "<h3>Obres amb l'any d'edició probablement incorrecte</h3>";
    if ($edition_year_output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$edition_year_output}</pre>";
    }
}

function test_paremies_any_erroni(): void
{
    require_once __DIR__ . '/../common.php';

    echo "<h3>Modismes amb l'any probablement incorrecte</h3>";
    $current_year = (int) date('Y');
    $stmt = db_query('SELECT `MODISME`, `Any` FROM `00_PAREMIOTIPUS` WHERE CAST(`Any` AS SIGNED) < 0 OR CAST(`Any` AS SIGNED) > ' . $current_year);
    $output = '';
    $rows = $stmt->fetchAll(PDO::FETCH_NUM);
    foreach ($rows as $row) {
        [$modisme, $year] = $row;
        assert(is_string($modisme));
        assert(is_string($year));
        $output .= $modisme . ' (' . $year . ")\n";
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
    $current_year = (int) date('Y');
    $stmt = db_query('SELECT `Identificador`, `Any` FROM `00_IMATGES` WHERE CAST(`Any` AS SIGNED) < 0 OR CAST(`Any` AS SIGNED) > ' . $current_year);
    $output = '';
    $images = $stmt->fetchAll(PDO::FETCH_NUM);
    foreach ($images as $image) {
        [$id, $year] = $image;
        assert(is_string($id));
        assert(is_string($year));
        $output .= 'paremies/' . $id . ' (' . $year . ")\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}
