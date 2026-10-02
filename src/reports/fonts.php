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

function test_fonts_buides(): void
{
    require_once __DIR__ . '/../common.php';

    $stmt = db_query('SELECT `Identificador`, `Títol`, `Autor` FROM `00_FONTS` WHERE `Identificador` IS NULL OR LENGTH(`Identificador`) < 1');
    echo '<h3>Registres a la taula 00_FONTS amb el camp Identificador buit</h3>';
    $output = '';
    while (($record = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
        $title = $record['Títol'];
        $author = $record['Autor'];
        assert(is_string($title) || $title === null);
        assert(is_string($author) || $author === null);
        $output .= 'Títol: ' . ($title ?? '') . ', Autor: ' . ($author ?? '') . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    $stmt = db_query('SELECT `Identificador`, `Autor` FROM `00_FONTS` WHERE `Títol` IS NULL OR LENGTH(`Títol`) < 2');
    echo '<h3>Registres a la taula 00_FONTS amb el camp Títol buit</h3>';
    $output = '';
    while (($record = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
        $identifier = $record['Identificador'];
        $author = $record['Autor'];
        assert(is_string($identifier) || $identifier === null);
        assert(is_string($author) || $author === null);
        $output .= 'Identificador: ' . ($identifier ?? '') . ', Autor: ' . ($author ?? '') . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    $stmt = db_query('SELECT `Identificador`, `Títol` FROM `00_FONTS` WHERE `Autor` IS NULL OR LENGTH(`Autor`) < 2');
    echo '<h3>Registres a la taula 00_FONTS amb el camp Autor buit</h3>';
    $output = '';
    while (($record = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
        $identifier = $record['Identificador'];
        $title = $record['Títol'];
        assert(is_string($identifier) || $identifier === null);
        assert(is_string($title) || $title === null);
        $output .= 'Identificador: ' . ($identifier ?? '') . ', Títol: ' . ($title ?? '') . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}

function test_fonts_sense_paremia(): void
{
    require_once __DIR__ . '/../common.php';

    $fonts = get_fonts_paremiotipus();
    $stmt = db_query('SELECT DISTINCT `ID_FONT`, 1 FROM `00_PAREMIOTIPUS`');
    $fonts_modismes = [];
    while (($result = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
        assert(is_string($result[0]));
        $fonts_modismes[$result[0]] = $result[1];
    }

    echo '<h3>Obres de la taula 00_FONTS que no estan referenciades per cap parèmia</h3>';
    $output = '';
    foreach ($fonts as $identificador => $title) {
        if (!isset($fonts_modismes[$identificador])) {
            $output .= '<a href="' . get_obra_url($identificador) . '">' . $title . '</a><br>';
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<div style='font-size: 13px;'>{$output}</div>";
    }
}

function test_paremies_sense_font_existent(): void
{
    require_once __DIR__ . '/../common.php';

    $fonts = get_fonts_paremiotipus();
    $stmt = db_query('SELECT `MODISME`, `ID_FONT` FROM `00_PAREMIOTIPUS` ORDER BY `ID_FONT`');

    echo '<h3>Parèmies que tenen obra, però que aquesta no es troba a la taula 00_FONTS</h3>';
    $prev = '';
    $output = '';
    while (($paremia = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
        assert(is_string($paremia['MODISME']));
        assert(is_string($paremia['ID_FONT']));
        if ($paremia['ID_FONT'] !== '' && !isset($fonts[$paremia['ID_FONT']])) {
            if ($prev !== $paremia['ID_FONT']) {
                if ($prev !== '') {
                    $output .= "\n\n";
                }
                $output .= $paremia['ID_FONT'] . ':';
            }
            $output .= "\n    " . $paremia['MODISME'];
            $prev = $paremia['ID_FONT'];
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}
