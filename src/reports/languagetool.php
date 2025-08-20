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

function test_languagetool(): void
{
    echo "<h3>Nous paremiotipus detectats amb LanguageTool des de l'última actualització</h3>";
    $text = @file_get_contents(__DIR__ . '/../../scripts/languagetool-checker/excluded_new.txt');
    if ($text !== false) {
        echo 'Total: ' . format_nombre(substr_count($text, "\n"));
        echo "<details open><pre>{$text}</pre></details>";
    }

    echo '<h3>Paremiotipus detectats amb LanguageTool</h3>';
    $text = file_get_contents(__DIR__ . '/../../scripts/languagetool-checker/excluded.txt');
    if ($text !== false) {
        echo '<i>A causa d\'errors tipogràfics, ortogràfics, per incloure paraules malsonants, noms propis, localismes o falsos positius.</i>';
        echo '<br>Total: ' . format_nombre(substr_count($text, "\n"));
        echo "<details><pre>{$text}</pre></details>";
    }
}
