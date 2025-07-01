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

    echo '<h3>Registres a la taula 00_FONTS amb el camp Títol buit</h3>';
    $records = get_db()->query('SELECT `Identificador` FROM `00_FONTS` WHERE `Títol` IS NULL OR LENGTH(`Títol`) < 2')->fetchAll(PDO::FETCH_COLUMN);
    echo '<pre>';
    foreach ($records as $r) {
        echo "{$r}\n";
    }
    echo '</pre>';

    echo '<h3>Registres a la taula 00_FONTS amb el camp Autor buit</h3>';
    $records = get_db()->query('SELECT `Identificador` FROM `00_FONTS` WHERE `Autor` IS NULL OR LENGTH(`Autor`) < 2')->fetchAll(PDO::FETCH_COLUMN);
    echo '<pre>';
    foreach ($records as $record) {
        echo "{$record}\n";
    }
    echo '</pre>';
}

function test_fonts_zero(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus amb almenys 1 registre sense detalls a la font</h3>';
    echo '<div style="font-size: 13px;">';
    $file = file_get_contents(__DIR__ . '/../../tmp/test_zero_fonts.txt');
    if ($file !== false) {
        $lines = explode("\n", $file);
        foreach ($lines as $line) {
            echo html_escape_and_link_urls($line) . '<br>';
        }
    }
    echo '</div>';
}

function test_fonts_sense_paremia(): void
{
    require_once __DIR__ . '/../common.php';

    $fonts = get_fonts_paremiotipus();
    $fonts_modismes = get_db()->query('SELECT DISTINCT `ID_FONT`, 1 FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_KEY_PAIR);

    echo '<h3>Obres de la taula 00_FONTS que no estan referenciades per cap parèmia</h3>';
    echo '<div style="font-size: 13px;">';
    foreach ($fonts as $identificador => $title) {
        if (!isset($fonts_modismes[$identificador])) {
            echo '<a href="' . get_obra_url($identificador) . '">' . $title . '</a><br>';
        }
    }
    echo '</div>';
}

function test_paremies_sense_font_existent(): void
{
    require_once __DIR__ . '/../common.php';

    $fonts = get_fonts_paremiotipus();
    $paremies = get_db()->query('SELECT `MODISME`, `ID_FONT` FROM `00_PAREMIOTIPUS` ORDER BY `ID_FONT`')->fetchAll(PDO::FETCH_ASSOC);

    echo '<h3>Parèmies que tenen obra, però que aquesta no es troba a la taula 00_FONTS</h3>';
    echo '<pre>';
    $prev = '';
    foreach ($paremies as $paremia) {
        assert(is_string($paremia['ID_FONT']));
        if ($paremia['ID_FONT'] !== '' && !isset($fonts[$paremia['ID_FONT']])) {
            if ($prev !== $paremia['ID_FONT']) {
                if ($prev !== '') {
                    echo "\n\n";
                }
                echo $paremia['ID_FONT'] . ':';
            }
            echo "\n    " . $paremia['MODISME'];
            $prev = $paremia['ID_FONT'];
        }
    }
    echo '</pre>';
}
