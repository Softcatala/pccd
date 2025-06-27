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

function stats_paremiotipus(): void
{
    // phpcs:disable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys
    require_once __DIR__ . '/../common.php';

    require_once __DIR__ . '/../reports_common.php';

    $paremiotipus_count = get_paremiotipus_count();
    $modisme_count = get_modisme_count();
    $unique_modisme_diff = (int) get_db()->query('SELECT COUNT(DISTINCT `MODISME`) FROM `00_PAREMIOTIPUS`')->fetchColumn();
    echo '<script src="/admin/js/chart.min.js"></script>';
    echo '<h3>Paremiotipus per nombre de recurrències</h3>';
    $stmt = get_db()->query('SELECT `PAREMIOTIPUS`, COUNT(1) AS `MODISME_COUNT` FROM `00_PAREMIOTIPUS` GROUP BY `PAREMIOTIPUS`');
    $grouped_data = group_data_stats($stmt->fetchAll(PDO::FETCH_ASSOC), 'MODISME_COUNT');
    echo get_chart('bar', $grouped_data, 'paremiotipus', x_title: 'Nombre de recurrències', y_title: 'Paremiotipus', style: 'width:1000px;');

    echo '<h3>Paremiotipus per nombre de variants</h3>';
    $stmt = get_db()->query('SELECT `PAREMIOTIPUS`, COUNT(DISTINCT `MODISME`) AS `DISTINCT_MODISME_COUNT` FROM `00_PAREMIOTIPUS` GROUP BY `PAREMIOTIPUS`');
    $grouped_data = group_data_stats($stmt->fetchAll(PDO::FETCH_ASSOC), 'DISTINCT_MODISME_COUNT');
    echo get_chart('bar', $grouped_data, 'paremiotipus', x_title: 'Nombre de variants', y_title: 'Paremiotipus', style: 'width:1000px;');

    echo '<div style="display: flex; flex-wrap: wrap; gap: 3rem;">';
    echo '<article>';
    echo '<h3>Paremiotipus amb equivalents</h3>';
    $stmt = get_db()->query('SELECT COUNT(DISTINCT `PAREMIOTIPUS`) FROM `00_PAREMIOTIPUS` WHERE `EQUIVALENT` IS NOT NULL');
    $total = (int) $stmt->fetchColumn();
    $data = [
        'Amb equivalents' => $total,
        'Sense equivalents' => $paremiotipus_count - $total,
    ];
    echo get_chart('pie', $data, 'paremiotipus', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Paremiotipus amb sinònims</h3>';
    $stmt = get_db()->query('SELECT COUNT(DISTINCT `PAREMIOTIPUS`) FROM `00_PAREMIOTIPUS` WHERE `SINONIM` IS NOT NULL');
    $total = (int) $stmt->fetchColumn();
    $data = [
        'Amb sinònims' => $total,
        'Sense sinònims' => $paremiotipus_count - $total,
    ];
    echo get_chart('pie', $data, 'paremiotipus', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Paremiotipus amb imatges</h3>';
    $stmt = get_db()->query('SELECT COUNT(DISTINCT `PAREMIOTIPUS`) FROM `00_IMATGES`');
    $total = (int) $stmt->fetchColumn();
    $data = [
        'Amb imatges' => $total,
        'Sense imatges' => $paremiotipus_count - $total,
    ];
    echo get_chart('pie', $data, 'paremiotipus', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Paremiotipus / Common Voice</h3>';
    $stmt = get_db()->query('SELECT COUNT(DISTINCT `paremiotipus`) FROM `commonvoice`');
    $total = (int) $stmt->fetchColumn();
    $data = [
        'Amb veus' => $total,
        'Sense veus' => $paremiotipus_count - $total,
    ];
    echo get_chart('pie', $data, 'paremiotipus', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Paremiotipus / LanguageTool</h3>';
    $text = file_get_contents(__DIR__ . '/../../scripts/languagetool-checker/excluded.txt');
    if ($text !== false) {
        $total = substr_count($text, "\n");
        $data = [
            'Sense errors' => $paremiotipus_count - $total,
            'Amb errors' => $total,
        ];
        echo get_chart('pie', $data, 'paremiotipus', style: 'width:330px;');
    }
    echo '</article>';
    echo '</div>';

    echo '<div style="display: flex; flex-wrap: wrap; gap: 3rem;">';
    echo '<article>';
    echo '<h3>Fitxes amb equivalents</h3>';
    $stmt = get_db()->query('SELECT COUNT(1) FROM `00_PAREMIOTIPUS` WHERE `EQUIVALENT` IS NOT NULL');
    $total = (int) $stmt->fetchColumn();
    $data = [
        'Amb equivalents' => $total,
        'Sense equivalents' => $modisme_count - $total,
    ];
    echo get_chart('pie', $data, 'fitxes', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Fitxes amb sinònims</h3>';
    $stmt = get_db()->query('SELECT COUNT(1) FROM `00_PAREMIOTIPUS` WHERE `SINONIM` IS NOT NULL');
    $total = (int) $stmt->fetchColumn();
    $data = [
        'Amb sinònims' => $total,
        'Sense sinònims' => $modisme_count - $total,
    ];
    echo get_chart('pie', $data, 'fitxes', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Fitxes amb explicacions</h3>';
    $stmt = get_db()->query('SELECT COUNT(1) FROM `00_PAREMIOTIPUS` WHERE `EXPLICACIO` IS NOT NULL');
    $total = (int) $stmt->fetchColumn();
    $data = [
        'Amb explicacions' => $total,
        'Sense explicacions' => $modisme_count - $total,
    ];
    echo get_chart('pie', $data, 'fitxes', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Fitxes amb exemples</h3>';
    $stmt = get_db()->query('SELECT COUNT(1) FROM `00_PAREMIOTIPUS` WHERE `EXEMPLES` IS NOT NULL');
    $total = (int) $stmt->fetchColumn();
    $data = [
        'Amb exemples' => $total,
        'Sense exemples' => $modisme_count - $total,
    ];
    echo get_chart('pie', $data, 'fitxes', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Fitxes amb llocs</h3>';
    $stmt = get_db()->query('SELECT COUNT(1) FROM `00_PAREMIOTIPUS` WHERE `LLOC` IS NOT NULL');
    $total = (int) $stmt->fetchColumn();
    $data = [
        'Amb llocs' => $total,
        'Sense llocs' => $modisme_count - $total,
    ];
    echo get_chart('pie', $data, 'fitxes', style: 'width:330px;');
    echo '</article>';
    echo '</div>';

    echo '<article>';
    echo '<h3>Paremiotipus que no coincideixen amb cap dels seus modismes</h3>';
    $records = get_db()->query('
        SELECT
            `Display`
        FROM
            `paremiotipus_display`
        LEFT JOIN
            `00_PAREMIOTIPUS`
        ON
            `paremiotipus_display`.`Paremiotipus` = `00_PAREMIOTIPUS`.`MODISME`
        WHERE
            `MODISME` IS NULL
    ')->fetchAll(PDO::FETCH_COLUMN);

    $list = '';
    $total = 0;
    foreach ($records as $r) {
        $list .= $r . "\n";
        $total++;
    }
    $data = [
        'Amb coincidència' => $paremiotipus_count - $total,
        'Sense coincidència' => $total,
    ];
    echo "<details><summary>Mostra la llista</summary><pre>{$list}</pre></details>";
    echo get_chart('pie', $data, 'paremiotipus', style: 'width:330px;');
    echo '</article>';

    echo '<article>';
    echo '<h3>Modismes sense cap paraula en comú amb el seu paremiotipus</h3>';
    $modisme_count_diff = 0;
    $lines = file(__DIR__ . '/../../tmp/test_intl_modismes_molt_diferents.txt');
    $list = '';
    if ($lines !== false) {
        foreach ($lines as $line) {
            if (str_starts_with($line, '    ')) {
                $modisme_count_diff++;
            }
            $list .= $line;
        }
    }
    $data = [
        'Amb coincidència' => $unique_modisme_diff - $modisme_count_diff,
        'Sense coincidència' => $modisme_count_diff,
    ];
    echo "<details><summary>Mostra la llista</summary><pre>{$list}</pre></details>";
    echo get_chart('pie', $data, 'modismes (únics)', style: 'width:330px;');
    echo '</article>';

    echo '<h3>Creixement històric de la base de dades</h3>';
    $directory_path = __DIR__ . '/../../tests/playwright/data/historic/';
    $files = scandir($directory_path);
    assert(is_array($files));
    $paremiotipus_number_data = get_data_from_files($files, $directory_path, 'paremiotipusNumber');
    $fitxes_number_data = get_data_from_files($files, $directory_path, 'fitxesNumber');
    echo get_chart('line', $paremiotipus_number_data, 'paremiotipus', 'Mesos (2023-)', 'Nombre de registres', style: 'width:800px;');
    echo get_chart('line', $fitxes_number_data, 'fitxes', 'Mesos (2023-)', 'Nombre de registres', style: 'width:800px;');
}
