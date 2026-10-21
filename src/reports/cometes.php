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

function test_cometes(): void
{
    require_once __DIR__ . '/../common.php';

    // Non-standard quote characters: acute accent, grave accent, curly single
    // quotes (including low-9 variants), and curly double quotes (including
    // low-9 variants). Plain ' and " are fine, and « » are the standard
    // Catalan quotation marks, so they are not reported.
    $quotes = '[´`’‘‚‛“”„‟]';

    $checks = [
        ['Paremiotipus amb cometes no estàndard', "SELECT `Display` FROM `paremiotipus_display` WHERE `Display` REGEXP '{$quotes}' ORDER BY `Display`", false],
        ['Paremiotipus de les imatges amb cometes no estàndard', "SELECT DISTINCT `PAREMIOTIPUS` FROM `00_IMATGES` WHERE `PAREMIOTIPUS` REGEXP '{$quotes}' ORDER BY `PAREMIOTIPUS`", true],
        ['Paremiotipus multilingües amb cometes no estàndard', "SELECT DISTINCT `PAREMIOTIPUS` FROM `RML` WHERE `PAREMIOTIPUS` REGEXP '{$quotes}' ORDER BY `PAREMIOTIPUS`", true],
        ['Modismes amb cometes no estàndard', "SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` REGEXP '{$quotes}' ORDER BY `MODISME`", false],
    ];

    foreach ($checks as [$title, $query, $map_display]) {
        echo "<h3>{$title}</h3>";
        $values = db_query($query)->fetchAll(PDO::FETCH_COLUMN);

        $output = '';
        foreach ($values as $value) {
            $output .= ($map_display ? get_paremiotipus_display($value) : $value) . "\n";
        }

        if ($output === '') {
            echo '<pre class="empty">(cap resultat)</pre>';
        } else {
            echo 'Total: ' . count($values) . '<br>';
            echo '<details' . ($map_display ? ' open' : '') . "><pre>{$output}</pre></details>";
        }
    }
}
