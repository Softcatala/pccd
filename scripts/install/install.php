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

/*
 * Installation script.
 *
 * This file is called by scripts/install.sh.
 */

ini_set('memory_limit', '512M');

require __DIR__ . '/../../src/common.php';

require __DIR__ . '/../../src/install_common.php';

// Check that latest table has already been created to know if we are ready to proceed with the installation process.
if (!table_exists('paremiotipus_display')) {
    echo "Not ready to install\n";

    exit;
}

// Check that installation has not been executed already.
if (table_exists('pccd_is_installed')) {
    echo "Already installed\n";

    exit;
}

// TODO: DRY.
echo date('[H:i:s]') . ' standardizing quotes...' . "\n";
get_db()->exec("UPDATE `00_PAREMIOTIPUS` SET `MODISME` = REPLACE(REPLACE(REPLACE(REPLACE(`MODISME`, '´', '\\''), '`', '\\''), '’', '\\''), '‘', '\\''), `PAREMIOTIPUS` = REPLACE(REPLACE(REPLACE(REPLACE(`PAREMIOTIPUS`, '´', '\\''), '`', '\\''), '’', '\\''), '‘', '\\'')");
get_db()->exec("UPDATE `00_IMATGES` SET `PAREMIOTIPUS` = REPLACE(REPLACE(REPLACE(REPLACE(`PAREMIOTIPUS`, '´', '\\''), '`', '\\''), '’', '\\''), '‘', '\\'')");
get_db()->exec("UPDATE `RML` SET `PAREMIOTIPUS` = REPLACE(REPLACE(REPLACE(REPLACE(`PAREMIOTIPUS`, '´', '\\''), '`', '\\''), '’', '\\''), '‘', '\\'')");

echo date('[H:i:s]') . ' preprocessing columns for improved sorting and display...' . "\n";
$insert_display_stmt = db_prepare('INSERT IGNORE INTO `paremiotipus_display`(`Paremiotipus`, `Display`) VALUES(?, ?)');
$display_values_stmt = db_query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`');
while (($paremiotipus = $display_values_stmt->fetch(PDO::FETCH_COLUMN)) !== false) {
    assert(is_string($paremiotipus));
    $insert_display_stmt->execute([clean_paremiotipus_for_sorting($paremiotipus), $paremiotipus]);
}

$update_accepcio_stmt = db_prepare('UPDATE `00_PAREMIOTIPUS` SET `MODISME` = ?, `ACCEPCIO` = ? WHERE `Id` = ?');
$update_sorting_stmt = db_prepare('UPDATE `00_PAREMIOTIPUS` SET `PAREMIOTIPUS` = ? WHERE `Id` = ?');
$paremiotipus_stmt = db_query('SELECT `Id`, `PAREMIOTIPUS`, `MODISME` FROM `00_PAREMIOTIPUS`');
while (($paremiotipus = $paremiotipus_stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
    // Try to clean phrases ending with numbers and fill ACCEPCIO field instead.
    // TODO: ideally this should be handled in the DB side.
    assert(is_string($paremiotipus['MODISME']));
    $modisme = trim($paremiotipus['MODISME']);
    if (preg_match_all('/ ([1-4])$/', $modisme, $matches) === 1) {
        $suffix = end($matches[0]);
        if (is_string($suffix)) {
            $accepcio = trim($suffix);
            $modisme = rtrim($modisme, $accepcio . DEFAULT_TRIM_CHARS);
            $update_accepcio_stmt->execute([$modisme, $accepcio, $paremiotipus['Id']]);
        }
    }

    // Clean `—` and other characters from the beginning, to improve sorting.
    assert(is_string($paremiotipus['PAREMIOTIPUS']));
    $cleaned_paremiotipus = clean_paremiotipus_for_sorting($paremiotipus['PAREMIOTIPUS']);
    if ($cleaned_paremiotipus !== $paremiotipus['PAREMIOTIPUS']) {
        $update_sorting_stmt->execute([$cleaned_paremiotipus, $paremiotipus['Id']]);
    }
}

echo date('[H:i:s]') . ' normalizing paremiotipus in images table...' . "\n";
$normalize_images_stmt = db_prepare('UPDATE `00_IMATGES` SET `PAREMIOTIPUS` = ? WHERE `Comptador` = ?');
$images_stmt = db_query('SELECT `Comptador`, `PAREMIOTIPUS` FROM `00_IMATGES`');
while (($image = $images_stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
    assert(is_string($image['PAREMIOTIPUS']));
    $cleaned_paremiotipus = clean_paremiotipus_for_sorting($image['PAREMIOTIPUS']);
    if ($cleaned_paremiotipus !== $image['PAREMIOTIPUS']) {
        $normalize_images_stmt->execute([$cleaned_paremiotipus, $image['Comptador']]);
    }
}

echo date('[H:i:s]') . ' normalizing paremiotipus in multilingüe...' . "\n";
$normalize_rml_stmt = db_prepare('UPDATE `RML` SET `PAREMIOTIPUS` = ? WHERE `NUM_ORDRE` = ?');
$rml_stmt = db_query('SELECT `NUM_ORDRE`, `PAREMIOTIPUS` FROM `RML`');
while (($record = $rml_stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
    assert(is_string($record['PAREMIOTIPUS']));
    $cleaned_paremiotipus = clean_paremiotipus_for_sorting($record['PAREMIOTIPUS']);
    if ($cleaned_paremiotipus !== $record['PAREMIOTIPUS']) {
        $normalize_rml_stmt->execute([$cleaned_paremiotipus, $record['NUM_ORDRE']]);
    }
}

echo date('[H:i:s]') . ' importing top 10000 paremiotipus...' . "\n";
$insert_stmt = db_prepare('INSERT INTO `common_paremiotipus`(`Paremiotipus`, `Compt`) VALUES(?, ?)');
$popular_paremiotipus_stmt = db_query('SELECT
        `PAREMIOTIPUS`,
        COUNT(1) AS `POPULAR`
    FROM
        `00_PAREMIOTIPUS`
    GROUP BY
        `PAREMIOTIPUS`
    ORDER BY
        `POPULAR` DESC
    LIMIT 10000');
while (($result = $popular_paremiotipus_stmt->fetch(PDO::FETCH_NUM)) !== false) {
    [$paremiotipus, $popularity] = $result;
    assert(is_string($paremiotipus));
    assert(is_string($popularity));
    $insert_stmt->execute([$paremiotipus, $popularity]);
}

echo date('[H:i:s]') . ' storing image dimensions...' . "\n";
store_image_dimensions('00_IMATGES', 'Identificador', 'docroot/img/imatges');
store_image_dimensions('00_FONTS', 'Imatge', 'docroot/img/obres');
store_image_dimensions('00_OBRESVPR', 'Imatge', 'docroot/img/obres');

echo date('[H:i:s]') . ' database installation has finished!' . "\n";
get_db()->exec('CREATE TABLE `pccd_is_installed`(`id` int)');
