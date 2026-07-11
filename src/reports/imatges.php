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

function test_imatges_paremiotipus(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus de la taula 00_IMATGES que no concorda amb cap paremiotipus de la taula 00_PAREMIOTIPUS</h3>';
    $stmt = db_query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_IMATGES` WHERE `PAREMIOTIPUS` NOT IN (SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`)');
    $paremiotipus = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($paremiotipus as $p) {
        // No need to run it through get_paremiotipus_display() as it won't exist there.
        $output .= $p . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}

function test_imatges_extensions(): void
{
    echo "<h3>Fitxers d'imatge amb extensió o format inconsistents</h3>";
    $output = trim((string) @file_get_contents(__DIR__ . '/../../data/reports/test_imatges_extensions.txt'));
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo "<h3>Fitxers d'imatge amb extensió no suportada, en majúscules o no estàndard (gif/jpg/png)</h3>";
    $output = trim((string) @file_get_contents(__DIR__ . '/../../data/reports/test_imatges_file_extensions.txt'));
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}

function test_imatges_format(): void
{
    echo '<h3>Imatges massa petites (menys de 350 píxels d\'amplada)</h3>';
    echo '<i>Si fos possible, haurien de ser de 500 px o més.</i>';
    $output = trim((string) @file_get_contents(__DIR__ . '/../../data/reports/test_imatges_petites.txt'));
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }

    echo '<h3>Imatges amb possibles problemes de format</h3>';
    $output = trim((string) @file_get_contents(__DIR__ . '/../../data/reports/test_imatges_format.txt'));
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}

function test_imatges_no_reconegudes(): void
{
    require_once __DIR__ . '/../common.php';

    require_once __DIR__ . '/../reports_common.php';

    echo '<h3>Imatges a la BD amb extensió no estàndard (gif/jpg/png) o en majúscules</h3>';
    $stmt = db_query('SELECT `Imatge` FROM `00_FONTS`');
    $imatges = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($imatges as $i) {
        assert(is_string($i));
        if ($i !== '' && !has_supported_image_extension($i)) {
            $output .= 'cobertes/' . $i . "\n";
        }
    }
    $stmt = db_query('SELECT `Identificador` FROM `00_IMATGES`');
    $imatges = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($imatges as $i) {
        assert(is_string($i));
        if ($i !== '' && !has_supported_image_extension($i)) {
            $output .= 'paremies/' . $i . "\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo "<h3>Imatges que no s'ha pogut detectar la seva mida</h3>";
    $stmt = db_query('SELECT `Imatge`, `WIDTH`, `HEIGHT` FROM `00_FONTS`');
    $imatges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $output = '';
    foreach ($imatges as $i) {
        if ($i['Imatge'] !== '' && ($i['WIDTH'] === '0' || $i['HEIGHT'] === '0')) {
            $output .= 'cobertes/' . $i['Imatge'] . "\n";
        }
    }
    $stmt = db_query('SELECT `Identificador`, `WIDTH`, `HEIGHT` FROM `00_IMATGES`');
    $imatges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($imatges as $imatge) {
        if ($imatge['Identificador'] !== '' && ($imatge['WIDTH'] === '0' || $imatge['HEIGHT'] === '0')) {
            $output .= 'paremies/' . $imatge['Identificador'] . "\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}

function test_imatges_minuscules(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Cobertes a la BD amb minúscules al nom</h3>';
    $imatges = db_query('SELECT `Imatge` FROM `00_FONTS`')->fetchAll(PDO::FETCH_COLUMN);
    $output = '';
    foreach ($imatges as $filename) {
        assert(is_string($filename));
        if ($filename !== '') {
            $name = pathinfo($filename, PATHINFO_FILENAME);
            if ($name !== mb_strtoupper($name)) {
                $output .= $filename . "\n";
            }
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}

function test_imatges_sense_paremiotipus(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Camp PAREMIOTIPUS buit a la taula 00_IMATGES</h3>';
    $stmt = db_query('SELECT `Identificador`, `PAREMIOTIPUS` FROM `00_IMATGES`');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $output = '';
    foreach ($results as $result) {
        assert(is_string($result['PAREMIOTIPUS']));
        if (strlen($result['PAREMIOTIPUS']) < 2) {
            $output .= $result['Identificador'] . "\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }
}

function test_imatges_buides(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Fonts sense imatge</h3>';
    $stmt = db_query('SELECT `Imatge`, `Identificador` FROM `00_FONTS`');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $n = 0;
    $output = '';
    foreach ($results as $r) {
        assert(is_string($r['Imatge']));
        if (strlen($r['Imatge']) < 5) {
            $output .= $r['Identificador'] . "\n";
            $n++;
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "{$n} camps 'Imatge' buits a la taula 00_FONTS:";
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Registres a la taula 00_IMATGES amb el camp Identificador buit</h3>';
    $stmt = db_query('SELECT `Identificador` FROM `00_IMATGES`');
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $n = 0;
    foreach ($results as $result) {
        if ($result === '') {
            $n++;
        }
    }
    if ($n > 0) {
        echo "<pre>{$n} camps 'Identificador' buits a la taula 00_IMATGES</pre>";
    } else {
        echo '<pre class="empty">(cap resultat)</pre>';
    }
}

function test_imatges_camps_duplicats(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus de la taula 00_IMATGES amb els mateixos camps URL_ENLLAÇ, AUTOR, DIARI i ARTICLE:</h3>';
    $stmt = db_query('SELECT
        `PAREMIOTIPUS`,
        `URL_ENLLAÇ`,
        `AUTOR`,
        `DIARI`,
        `ARTICLE`,
        GROUP_CONCAT(`Identificador`)
    FROM
        `00_IMATGES`
    WHERE
        `PAREMIOTIPUS` IS NOT NULL AND `PAREMIOTIPUS` <> \'\'
        AND `URL_ENLLAÇ` IS NOT NULL
        AND `AUTOR` IS NOT NULL
        AND `DIARI` IS NOT NULL
        AND `ARTICLE` IS NOT NULL
    GROUP BY
        `PAREMIOTIPUS`,
        `URL_ENLLAÇ`,
        `AUTOR`,
        `DIARI`,
        `ARTICLE`
    HAVING
        COUNT(*) > 1');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $prev = '';
    $output = '';
    foreach ($results as $r) {
        if ($prev !== $r['PAREMIOTIPUS']) {
            $output .= '<li><a href="' . get_paremiotipus_url($r['PAREMIOTIPUS']) . '">' . get_paremiotipus_display($r['PAREMIOTIPUS']) . '</a></li>';
        }
        $prev = $r['PAREMIOTIPUS'];
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><ul>{$output}</ul></details>";
    }
}

function test_imatges_no_existents(): void
{
    echo "<h3>Fitxers d'imatge que no s'han pogut generar correctament, o que no existeixen</h3>";
    $output = trim((string) @file_get_contents(__DIR__ . '/../../data/reports/test_imatges_no_existents.txt'));
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}

function test_imatges_duplicades(): void
{
    echo "<h3>Fitxers d'imatge duplicats</h3>";
    $output = trim((string) @file_get_contents(__DIR__ . '/../../data/reports/test_imatges_duplicades.txt'));
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }
}

function test_imatges_no_referenciades(): void
{
    echo "<h3>Fitxers d'imatge no referenciats</h3>";
    $output = trim((string) @file_get_contents(__DIR__ . '/../../data/reports/test_imatges_no_referenciades.txt'));
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}

function test_imatges_repetides(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Identificador repetit a la taula 00_IMATGES</h3>';
    $stmt = db_query('SELECT `Identificador` FROM `00_IMATGES`');
    $imatges = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // See https://stackoverflow.com/a/5995153/1391963.
    $repetides = array_unique(array_diff_assoc($imatges, array_unique($imatges)));
    $output = '';
    foreach ($repetides as $r) {
        $output .= $r . "\n";
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Número repetit a la taula 00_IMATGES</h3>';
    $stmt = db_query('SELECT `Identificador` FROM `00_IMATGES` ORDER BY `Identificador`');
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $prev = 0;
    $prev_image = '';
    $numbers = [];
    $output = '';
    foreach ($images as $image) {
        // Extract number at the beginning of the string.
        assert(is_string($image));
        $number = (int) preg_replace('/^(\d+).*/', '$1', $image);
        if ($number === $prev) {
            $output .= $prev_image . "\n";
            $output .= $image . "\n\n";
        }
        $prev = $number;
        $prev_image = $image;
        $numbers[$number] = true;
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Números no fets servir a la taula 00_IMATGES</h3>';
    $keys = array_keys($numbers);
    assert($keys !== []);
    $max = max($keys);
    $output = '';
    for ($i = 1; $i <= $max; $i++) {
        if (!isset($numbers[$i])) {
            $output .= $i . "\n";
        }
    }
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<details><pre>{$output}</pre></details>";
    }
}
