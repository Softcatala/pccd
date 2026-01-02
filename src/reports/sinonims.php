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

function test_sinonims(): void
{
    require_once __DIR__ . '/../common.php';

    require_once __DIR__ . '/../reports_common.php';

    $pdo = get_db();
    $parem_stmt = $pdo->prepare('SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` = :paremiotipus');
    $mod_stmt = $pdo->prepare('SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` = :modisme');
    $paremiotipus = $pdo->query('SELECT `PAREMIOTIPUS`, `SINONIM` FROM `00_PAREMIOTIPUS` WHERE `SINONIM` IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);

    $sinonims_array = [];
    foreach ($paremiotipus as $p) {
        $sinonims = get_sinonims($p['SINONIM']);
        foreach ($sinonims as $sin) {
            if (!isset($sinonims_array[$sin])) {
                $sinonims_array[$sin] = 0;
            }
            $sinonims_array[$sin]++;
        }
    }

    $matched = [];
    echo '<h3>Sinònims detectats 2 o més vegades que no existeixen com a modisme</h3>';
    echo "<i>Per a la detecció se separa el camp sinònim amb la barra vertical '|' i se suprimeixen fragments com ' / ', 'v.', 'V.', 'Veg.', 'Similar:'.</i>";
    echo '<details><pre>';
    $sinonims_array_truncated = array_filter($sinonims_array, static fn (int $value): bool => $value >= 2);
    arsort($sinonims_array_truncated);
    foreach ($sinonims_array_truncated as $s => $count) {
        // Try get a modisme for that sinònim.
        $mod_stmt->execute([':modisme' => $s]);
        $sm = $mod_stmt->fetchColumn();
        if ($sm === false) {
            $matched[$s] = true;
            echo "{$s} ({$count})\n";
        }
    }
    echo '</pre></details>';

    echo '<h3>Altres sinònims detectats 5 o més vegades que no són un paremiotipus</h3>';
    echo '<details><pre>';
    $sinonims_array_truncated = array_filter($sinonims_array, static fn (int $value): bool => $value >= 5);
    arsort($sinonims_array_truncated);
    foreach ($sinonims_array_truncated as $s => $count) {
        // Try get a paremiotipus for that sinònim.
        $parem_stmt->execute([':paremiotipus' => $s]);
        $sp = $parem_stmt->fetchColumn();
        // Skip results matched in the previous loop.
        if ($sp === false && !isset($matched[$s])) {
            echo "{$s} ({$count})\n";
        }
    }
    echo '</pre></details>';
}
