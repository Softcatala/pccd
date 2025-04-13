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

ini_set('memory_limit', '2048M');

require __DIR__ . '/../../src/common.php';

require __DIR__ . '/../../src/reports_common.php';

$pdo = get_db();
$parem = $pdo->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);

$parem_stmt = $pdo->prepare('SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` = :paremiotipus');
$modisme_stmt = $pdo->prepare('SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `MODISME` = :modisme');

$parem_sin_array = [];
foreach ($parem as $p) {
    assert(is_string($p));
    $parem_sin_array[$p][] = $p;

    $sin_stmt = $pdo->prepare('SELECT `SINONIM` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` = :paremiotipus AND `SINONIM` IS NOT NULL');
    $sin_stmt->execute([':paremiotipus' => $p]);
    $sinonims = $sin_stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($sinonims as $sinonim) {
        if ($sinonim === false) {
            continue;
        }

        $sinonims_array = get_sinonims($sinonim);
        foreach ($sinonims_array as $s) {
            // Try get a paremiotipus for that sinònim.
            $parem_stmt->execute([':paremiotipus' => $s]);
            $sp = $parem_stmt->fetchColumn();

            // Try to get the paremiotipus from the modisme.
            if ($sp === false) {
                $modisme_stmt->execute([':modisme' => $s]);
                $sp = $modisme_stmt->fetchColumn();
            }

            if ($sp === false) {
                continue;
            }

            assert(is_string($sp));
            if (in_array($sp, $parem_sin_array[$p], true)) {
                continue;
            }

            $parem_sin_array[$p][] = $sp;
        }
    }
}

foreach ($parem_sin_array as $array) {
    if (count($array) > 1) {
        fwrite(STDOUT, implode(' | ', $array) . "\n");
    }
}

// Group them if at least one sentence is common.
$new_array = [];
foreach ($parem_sin_array as $key => $value) {
    $found = false;
    foreach ($new_array as $new_key => $new_value) {
        // Check if any value in $value exists in $new_value
        if (array_intersect($value, $new_value) !== []) {
            $new_array[$new_key] = array_unique(array_merge($new_value, $value));
            $found = true;

            break;
        }
    }

    if (!$found) {
        $new_array[$key] = $value;
    }
}

foreach ($new_array as $array) {
    if (count($array) > 1) {
        fwrite(STDERR, implode(' | ', $array) . "\n");
    }
}
