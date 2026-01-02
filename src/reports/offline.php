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

const LEVENSHTEIN_SIMILARITY_THRESHOLD = 0.8;
const LEVENSHTEIN_MAX_DISTANCE = 12;

const WORD_DIFFERENCE_MIN = 1;

function background_test_llibres_urls(): string
{
    require_once __DIR__ . '/../common.php';

    require_once __DIR__ . '/../reports_common.php';

    $output = '';
    $urls = [];
    $llibres = get_db()->query('SELECT * FROM `00_OBRESVPR`')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($llibres as $llibre) {
        if ($llibre['URL'] === '') {
            $output .= 'URL buida (Identificador ' . $llibre['Títol'] . ")\n";

            continue;
        }
        assert(is_string($llibre['URL']));

        $url = $llibre['URL'];
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $output .= 'URL no vàlida (Identificador ' . $llibre['Títol'] . '): ' . $url . "\n";

            continue;
        }

        if (isset($urls[$url])) {
            continue;
        }

        $response_code = curl_get_response_code(url: $url, nobody: false);
        $urls[$url] = $response_code;
        if (in_array($response_code, [0, 200, 301, 302, 307, 308], true)) {
            continue;
        }

        $output .= 'HTTP ' . $response_code . ' (' . $llibre['Títol'] . '): ' . $url . "\n";
    }

    return $output;
}

function background_test_fonts_urls(): string
{
    require_once __DIR__ . '/../common.php';

    require_once __DIR__ . '/../reports_common.php';

    $output = '';
    $urls = [];
    $fonts = get_db()->query('SELECT * FROM `00_FONTS`')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fonts as $font) {
        assert(is_string($font['URL']));
        $url = trim($font['URL']);
        if ($url === '') {
            continue;
        }

        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $output .= 'URL no vàlida (Identificador ' . $font['Identificador'] . '): ' . $url . "\n";
        } elseif (!isset($urls[$url])) {
            $response_code = curl_get_response_code($url);
            $urls[$url] = $response_code;
            if (in_array($response_code, [0, 200, 301, 302, 307, 308], true)) {
                continue;
            }

            $output .= 'HTTP ' . $response_code . ' (Identificador ' . $font['Identificador'] . '): ' . $url . "\n";
        }
    }

    return $output;
}

function background_test_imatges_urls(int $start = 0, int $end = 0): string
{
    require_once __DIR__ . '/../common.php';

    require_once __DIR__ . '/../reports_common.php';

    $output = '';
    $urls = [];
    $fonts = get_db()->query('SELECT * FROM `00_IMATGES`')->fetchAll(PDO::FETCH_ASSOC);

    $limit = $end;
    if ($limit === 0) {
        $limit = count($fonts);
    }

    for ($i = $start; $i < $limit; $i++) {
        $font = $fonts[$i];
        assert(is_string($font['URL_IMATGE']));
        $url = trim($font['URL_IMATGE']);
        if ($url === '') {
            continue;
        }

        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $output .= 'URL no vàlida (Identificador ' . $font['Identificador'] . '): ' . $url . "\n";
        } elseif (!isset($urls[$url])) {
            $response_code = curl_get_response_code($url);
            $urls[$url] = $response_code;
            if (in_array($response_code, [0, 200, 301, 302, 307, 308], true)) {
                continue;
            }

            $output .= 'HTTP ' . $response_code . ' (Identificador ' . $font['Identificador'] . '): ' . $url . "\n";
        }
    }

    return $output;
}

function background_test_imatges_links(int $start = 0, int $end = 0): string
{
    require_once __DIR__ . '/../common.php';

    require_once __DIR__ . '/../reports_common.php';

    $output = '';
    $urls = [];
    $fonts = get_db()->query('SELECT * FROM `00_IMATGES`')->fetchAll(PDO::FETCH_ASSOC);

    $limit = $end;
    if ($limit === 0) {
        $limit = count($fonts);
    }

    for ($i = $start; $i < $limit; $i++) {
        $font = $fonts[$i];
        $url = $font['URL_ENLLAÇ'];
        assert(is_string($url));
        if ($url === '') {
            continue;
        }

        if (isset($urls[$url])) {
            continue;
        }

        // Process URLs only once.
        $urls[$url] = true;

        // Discard wrong URLs.
        if (
            (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://'))
            || filter_var($url, \FILTER_SANITIZE_URL) !== $url
        ) {
            $output .= 'URL no vàlida o amb caràcters especials (Identificador ' . $font['Identificador'] . '): ' . $url . "\n";

            continue;
        }

        // Request URL.
        $response_code = curl_get_response_code($url);
        if (in_array($response_code, [0, 200, 301, 302, 307, 308], true)) {
            continue;
        }

        $output .= 'HTTP ' . $response_code . ' (Identificador ' . $font['Identificador'] . '): ' . $url . "\n";
    }

    return $output;
}

/**
 * Tries to find similar strings using levenshtein().
 *
 * Ideally, we should be using a mb_levenshtein() function (like the one that
 * may be implemented in PHP 8.5) or all strings should be transliterated. But
 * both options would be too costly, performance-wise. The current solution is
 * OK for now, as minor inaccuracies in similarity can be tolerated.
 */
function background_test_paremiotipus_repetits(int $start = 0, int $end = 0): string
{
    require_once __DIR__ . '/../common.php';

    $modismes = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $total = count($modismes);

    $limit = $end;
    if ($limit === 0) {
        $limit = $total;
    }

    $output = '';
    for ($i = $start; $i < $limit; $i++) {
        $value1 = $modismes[$i];
        assert(is_string($value1));
        $length1 = strlen($value1);

        for ($u = $i + 1; $u < $total; $u++) {
            $value2 = $modismes[$u];
            assert(is_string($value2));
            $length2 = strlen($value2);

            if (abs($length1 - $length2) >= LEVENSHTEIN_MAX_DISTANCE) {
                continue;
            }

            $similarity = 1 - (levenshtein($value1, $value2) / max($length1, $length2));

            if ($similarity < LEVENSHTEIN_SIMILARITY_THRESHOLD) {
                continue;
            }

            $output .= get_paremiotipus_display($value1, escape_html: false) . "\n" . get_paremiotipus_display($value2, escape_html: false) . "\n\n";
        }
    }

    return $output;
}

function background_test_paremiotipus_accents(): string
{
    require_once __DIR__ . '/../common.php';

    $paremiotipus = get_db()->query('SELECT DISTINCT BINARY
        `a`.`PAREMIOTIPUS`
    FROM
        `00_PAREMIOTIPUS` `a`,
        `00_PAREMIOTIPUS` `b`
    WHERE
        `a`.`PAREMIOTIPUS` = `b`.`PAREMIOTIPUS`
    AND
        BINARY `a`.`PAREMIOTIPUS` != `b`.`PAREMIOTIPUS`
    ')->fetchAll(PDO::FETCH_COLUMN);

    $output = '';
    foreach ($paremiotipus as $p) {
        $output .= $p . "\n";
    }

    return $output;
}

function background_test_html_escape_and_link_urls(): string
{
    require_once __DIR__ . '/../common.php';

    @unlink(__DIR__ . '/../../tmp/test_tmp_debug_html_escape_and_link_urls.txt');

    $records = get_db()->query('SELECT DISTINCT `Observacions` FROM `00_FONTS` WHERE `Observacions` IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($records as $r) {
        html_escape_and_link_urls(text: $r, debug: true);
    }

    $records = get_db()->query('SELECT DISTINCT `URL` FROM `00_FONTS` WHERE `URL` IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($records as $r) {
        html_escape_and_link_urls(text: $r, debug: true);
    }

    $records = get_db()->query('SELECT DISTINCT `URL_ENLLAÇ` FROM `00_IMATGES` WHERE `URL_ENLLAÇ` IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($records as $r) {
        html_escape_and_link_urls(text: $r, debug: true);
    }

    $records = get_db()->query('SELECT DISTINCT `ARTICLE` FROM `00_PAREMIOTIPUS` WHERE `ARTICLE` IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($records as $r) {
        html_escape_and_link_urls(text: $r, debug: true);
    }

    $string = file_get_contents(__DIR__ . '/../../tmp/test_tmp_debug_html_escape_and_link_urls.txt');
    assert($string !== false);

    return $string;
}

function background_test_imatges_no_existents(): string
{
    require_once __DIR__ . '/../common.php';

    $output = '';

    $stmt = get_db()->query('SELECT `Identificador` FROM `00_IMATGES`');
    $imatges = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($imatges as $imatge) {
        if ($imatge === '') {
            continue;
        }
        if (is_file(__DIR__ . '/../../docroot/img/imatges/' . $imatge)) {
            continue;
        }
        $output .= 'paremies/' . $imatge . "\n";
    }

    $stmt = get_db()->query('SELECT `Imatge` FROM `00_FONTS`');
    $imatges = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($imatges as $i) {
        if ($i === '') {
            continue;
        }
        if (is_file(__DIR__ . '/../../docroot/img/obres/' . $i)) {
            continue;
        }
        $output .= 'cobertes/' . $i . "\n";
    }

    return $output;
}

function background_test_imatges_no_referenciades(): string
{
    require_once __DIR__ . '/../common.php';

    $output = '';
    $images = get_db()->query('SELECT `Identificador`, 1 FROM `00_IMATGES`')->fetchAll(PDO::FETCH_KEY_PAIR);
    $dir = new DirectoryIterator(__DIR__ . '/../../docroot/img/imatges/');
    foreach ($dir as $file_info) {
        $filename = $file_info->getFilename();
        if ($filename === '.') {
            continue;
        }
        if ($filename === '..') {
            continue;
        }
        if ($filename === '.picasa.ini') {
            continue;
        }

        $extension = $file_info->getExtension();
        if (!in_array($extension, ['jpg', 'png', 'gif'], true)) {
            continue;
        }

        if (isset($images[$filename])) {
            continue;
        }

        $output .= "{$filename}\n";
    }

    $fonts = get_db()->query('SELECT `Imatge`, 1 FROM `00_FONTS`')->fetchAll(PDO::FETCH_KEY_PAIR);
    $llibres = get_db()->query('SELECT `Imatge`, 1 FROM `00_OBRESVPR`')->fetchAll(PDO::FETCH_KEY_PAIR);
    $dir = new DirectoryIterator(__DIR__ . '/../../docroot/img/obres/');
    foreach ($dir as $file_info) {
        $filename = $file_info->getFilename();
        if ($filename === '.') {
            continue;
        }
        if ($filename === '..') {
            continue;
        }
        if ($filename === '.picasa.ini') {
            continue;
        }

        $extension = $file_info->getExtension();
        if (!in_array($extension, ['jpg', 'png', 'gif'], true)) {
            continue;
        }
        if (isset($fonts[$filename])) {
            continue;
        }
        if (isset($llibres[$filename])) {
            continue;
        }

        $output .= "{$filename}\n";
    }

    return $output;
}

function background_test_intl_paremiotipus_sospitosos(): string
{
    require_once __DIR__ . '/../common.php';

    $checker = new Spoofchecker();

    $output = '';
    $modismes = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        assert(is_string($m));
        if ($checker->isSuspicious($m)) {
            $output .= get_paremiotipus_display($m, escape_html: false) . "\n";
        }
    }

    return $output;
}

function background_test_intl_modismes_sospitosos(): string
{
    require_once __DIR__ . '/../common.php';

    $checker = new Spoofchecker();

    $output = '';
    $modismes = get_db()->query('SELECT `MODISME` FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $modisme) {
        assert(is_string($modisme));
        if ($checker->isSuspicious($modisme)) {
            $output .= $modisme . "\n";
        }
    }

    return $output;
}

function background_test_intl_paremiotipus_repetits(): string
{
    require_once __DIR__ . '/../common.php';

    $checker = new Spoofchecker();

    $output = '';
    $prev = '';
    $paremiotipus = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        if ($checker->areConfusable($p, $prev)) {
            $output .= $prev . "\n" . get_paremiotipus_display($p, escape_html: false) . "\n\n";
        }
        $prev = $p;
    }

    return $output;
}

function background_test_intl_modismes_repetits(): string
{
    require_once __DIR__ . '/../common.php';

    $checker = new Spoofchecker();

    $output = '';
    $results = get_db()->query('SELECT DISTINCT `MODISME`, `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`, `MODISME`')->fetchAll(PDO::FETCH_ASSOC);
    $grouped_results = [];
    foreach ($results as $row) {
        $grouped_results[$row['PAREMIOTIPUS']][] = $row['MODISME'];
    }
    foreach ($grouped_results as $modisme_array) {
        $prev = '';
        foreach ($modisme_array as $modisme) {
            if ($prev !== '' && $checker->areConfusable($modisme, $prev)) {
                $output .= $prev . "\n" . $modisme . "\n\n";
            }
            $prev = $modisme;
        }
    }

    return $output;
}

function background_test_intl_modismes_molt_diferents(): string
{
    require_once __DIR__ . '/../common.php';

    $transliterator = Transliterator::create('Any-Latin; Latin-ASCII; [\u0100-\u7fff] remove');
    assert($transliterator instanceof Transliterator);

    $words_exclude = ['a', 'amb', 'de', 'el', 'els', 'en', 'i', 'la', 'les', 'ni', 'o', 'per', 'que'];
    $prev_paremiotipus = '';
    $output = '';
    $results = get_db()->query('SELECT DISTINCT `MODISME`, `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`, `MODISME`')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        if ($row['MODISME'] === $row['PAREMIOTIPUS']) {
            continue;
        }

        $modisme_ascii = $transliterator->transliterate($row['MODISME']);
        $paremiotipus_ascii = $transliterator->transliterate($row['PAREMIOTIPUS']);
        assert(is_string($modisme_ascii));
        assert(is_string($paremiotipus_ascii));

        $words_modisme = array_unique(array_diff(str_word_count(strtolower($modisme_ascii), 1), $words_exclude));
        $words_paremiotipus = array_unique(array_diff(str_word_count(strtolower($paremiotipus_ascii), 1), $words_exclude));

        $common_words = count(array_intersect($words_modisme, $words_paremiotipus));
        if ($common_words >= WORD_DIFFERENCE_MIN) {
            continue;
        }

        if ($prev_paremiotipus !== $row['PAREMIOTIPUS']) {
            if ($prev_paremiotipus !== '') {
                $output .= "\n";
            }
            $output .= get_paremiotipus_display($row['PAREMIOTIPUS'], escape_html: false) . ":\n";
        }
        $output .= '    ' . $row['MODISME'] . "\n";
        $prev_paremiotipus = $row['PAREMIOTIPUS'];
    }

    return $output;
}
