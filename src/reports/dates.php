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
    $fonts = db_query('SELECT `Identificador`, `Any`, `Any_edició` FROM `00_FONTS`');
    $any_output = '';
    $any_edicio_output = '';
    while (($font = $fonts->fetch(PDO::FETCH_NUM)) !== false) {
        [$identificador, $any, $any_edicio] = $font;
        assert(is_string($identificador));
        assert(is_string($any));
        assert(is_string($any_edicio));
        if (((int) $any) < 0 || ((int) $any) > $current_year) {
            $any_output .= $identificador . ' (' . $any . ")\n";
        }

        if (((int) $any_edicio) < 0 || ((int) $any_edicio) > $current_year) {
            $any_edicio_output .= $identificador . ' (' . $any_edicio . ")\n";
        }
    }

    echo "<h3>Obres amb l'any probablement incorrecte</h3>";
    if ($any_output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$any_output}</pre>";
    }

    echo "<h3>Obres amb l'any d'edició probablement incorrecte</h3>";
    if ($any_edicio_output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$any_edicio_output}</pre>";
    }
}

function test_paremies_any_erroni(): void
{
    require_once __DIR__ . '/../common.php';

    echo "<h3>Modismes amb l'any probablement incorrecte</h3>";
    $current_year = (int) date('Y');
    $stmt = db_query('SELECT `MODISME`, `Any` FROM `00_PAREMIOTIPUS` WHERE CAST(`Any` AS SIGNED) < 0 OR CAST(`Any` AS SIGNED) > ' . $current_year);
    $output = '';
    while (($result = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
        [$modisme, $any] = $result;
        assert(is_string($modisme));
        assert(is_string($any));
        $output .= $modisme . ' (' . $any . ")\n";
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
    while (($imatge = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
        [$identificador, $any] = $imatge;
        assert(is_string($identificador));
        assert(is_string($any));
        $output .= 'paremies/' . $identificador . ' (' . $any . ")\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}
