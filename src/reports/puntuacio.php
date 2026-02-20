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

function test_puntuacio(): void
{
    require_once __DIR__ . '/../common.php';

    $pdo = get_db();

    echo '<h3>Paremiotipus amb parèntesis o claudàtors no tancats</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE LENGTH(REPLACE(`PAREMIOTIPUS`, '(', '')) != LENGTH(REPLACE(`PAREMIOTIPUS`, ')', '')) OR LENGTH(REPLACE(`PAREMIOTIPUS`, '[', '')) != LENGTH(REPLACE(`PAREMIOTIPUS`, ']', ''))")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb cometes no tancades</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `Display` FROM `paremiotipus_display` WHERE (LENGTH(`Display`) - LENGTH(REPLACE(`Display`, '\"', ''))) % 2 != 0 OR LENGTH(REPLACE(`Display`, '«', '')) != LENGTH(REPLACE(`Display`, '»', '')) OR LENGTH(REPLACE(`Display`, '“', '')) != LENGTH(REPLACE(`Display`, '”', ''))")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo $p . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb cometa simple seguida del caràcter espai o signe de puntuació inusual</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE (`PAREMIOTIPUS` LIKE '%\\' %' OR `PAREMIOTIPUS` LIKE '%\\'.%' OR `PAREMIOTIPUS` LIKE '%\\',%' OR `PAREMIOTIPUS` LIKE '%\\';%' OR `PAREMIOTIPUS` LIKE '%\\':%' OR `PAREMIOTIPUS` LIKE '%\\'-%') AND (LENGTH(`PAREMIOTIPUS`) - LENGTH(REPLACE(`PAREMIOTIPUS`, '\\'', ''))) = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb el caràcter espai seguit de signe de puntuació inusual</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE (`PAREMIOTIPUS` LIKE '% .%' AND `PAREMIOTIPUS` NOT LIKE '% …%') OR `PAREMIOTIPUS` LIKE '% ,%' OR `PAREMIOTIPUS` LIKE '% ;%' OR `PAREMIOTIPUS` LIKE '% :%' OR `PAREMIOTIPUS` LIKE '% !%' OR `PAREMIOTIPUS` LIKE '% ?%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb 2 punts seguits</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%..%' OR `PAREMIOTIPUS` LIKE '%……%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb 4 punts seguits</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%….%' OR `PAREMIOTIPUS` LIKE '%.…%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb signes de puntuació repetits</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%::%' OR `PAREMIOTIPUS` LIKE '%;;%' OR `PAREMIOTIPUS` LIKE '%,,%' OR `PAREMIOTIPUS` LIKE '%--%' OR `PAREMIOTIPUS` LIKE '%;;%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus acabats amb signe de puntuació inusual</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%,' OR `PAREMIOTIPUS` LIKE '%;' OR `PAREMIOTIPUS` LIKE '%:'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb signe de puntuació seguit de lletres</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE (`PAREMIOTIPUS` REGEXP BINARY ',[a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY '[.][a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY '[)][a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY '[?][a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY ':[a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY ';[a-zA-Z]+') OR (`PAREMIOTIPUS` REGEXP BINARY '…[a-zA-Z]+')")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb una combinació de signes de puntuació inusual</h3>';
    echo '<pre>';
    $paremiotipus = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%?¿%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    $paremiotipus = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%,?%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    $paremiotipus = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%,!'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    $paremiotipus = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%!.'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    $paremiotipus = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%!…'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    $paremiotipus = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%?.'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    $paremiotipus = $pdo->query("SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` WHERE `PAREMIOTIPUS` LIKE '%?…'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        echo get_paremiotipus_display($p, escape_html: false) . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb possible confusió del caràcter <code>l</code> amb <code>I</code></h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE BINARY '%I\\'%' OR `MODISME` REGEXP BINARY '[a-z]+I'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb parèntesis o claudàtors no tancats</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE LENGTH(REPLACE(`MODISME`, '(', '')) != LENGTH(REPLACE(`MODISME`, ')', '')) OR LENGTH(REPLACE(`MODISME`, '[', '')) != LENGTH(REPLACE(`MODISME`, ']', ''))")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb cometes no tancades</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE (LENGTH(`MODISME`) - LENGTH(REPLACE(`MODISME`, '\"', ''))) % 2 != 0 OR LENGTH(REPLACE(`MODISME`, '«', '')) != LENGTH(REPLACE(`MODISME`, '»', '')) OR LENGTH(REPLACE(`MODISME`, '“', '')) != LENGTH(REPLACE(`MODISME`, '”', ''))")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb el caràcter espai seguit de signe de puntuació inusual</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE (`MODISME` LIKE '% .%' AND `MODISME` NOT LIKE '% …%') OR `MODISME` LIKE '% ,%' OR `MODISME` LIKE '% ;%' OR `MODISME` LIKE '% :%' OR `MODISME` LIKE '% !%' OR `MODISME` LIKE '% ?%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb 2 punts seguits</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%..%' OR `MODISME` LIKE '%……%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb 4 punts seguits</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%….%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb signes de puntuació repetits</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%::%' OR `MODISME` LIKE '%;;%' OR `MODISME` LIKE '%,,%' OR `MODISME` LIKE '%--%' OR `MODISME` LIKE '%;;%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb caràcters no reconeguts</h3>';
    echo '<details><pre>';
    $modismes = get_db()->query("SELECT DISTINCT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` REGEXP '[гпичäȧ́ä́ãõâêôûćčłø~ˆ¨ßşšžźŧ]'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre></details>';

    echo '<h3>Modismes acabats amb signe de puntuació inusual</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%,' OR `MODISME` LIKE '%;' OR `MODISME` LIKE '%:'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb una combinació de signes de puntuació inusual</h3>';
    echo '<pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%?¿%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%,?%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%,!'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%!.'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%!…'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%?.'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE `MODISME` LIKE '%?…'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb cometa simple seguida del caràcter espai o signe de puntuació inusual</h3>';
    echo '<details><pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE (`MODISME` LIKE '%\\' %' OR `MODISME` LIKE '%\\'.%' OR `MODISME` LIKE '%\\',%' OR `MODISME` LIKE '%\\';%' OR `MODISME` LIKE '%\\':%' OR `MODISME` LIKE '%\\'-%') AND (LENGTH(`MODISME`) - LENGTH(REPLACE(`MODISME`, '\\'', ''))) = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre></details>';

    echo '<h3>Modismes amb signe de puntuació seguit de lletres</h3>';
    echo '<details><pre>';
    $modismes = $pdo->query("SELECT `MODISME` FROM `00_PAREMIOTIPUS` WHERE (`MODISME` REGEXP BINARY ',[a-zA-Z]+') OR (`MODISME` REGEXP BINARY '[.][a-zA-Z]+') OR (`MODISME` REGEXP BINARY '[)][a-zA-Z]+') OR (`MODISME` REGEXP BINARY '[?][a-zA-Z]+') OR (`MODISME` REGEXP BINARY ':[a-zA-Z]+') OR (`MODISME` REGEXP BINARY ';[a-zA-Z]+')")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($modismes as $m) {
        echo $m . "\n";
    }
    echo '</pre></details>';
}

function test_paremiotipus_caracters_inusuals(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus amb caràcters inusuals en català</h3>';
    echo '<pre>';
    $paremiotipus = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        assert(is_string($p));
        $t = str_replace(
            ['à', 'è', 'é', 'í', 'ï', 'ò', 'ó', 'ú', 'ü', 'ç', '«', '»', '·', '–', '‑', '—', '―', '─', '…'],
            '',
            mb_strtolower($p)
        );
        if (
            // If it contains any non-ASCII character.
            preg_match('/[^\x00-\x7F]/', $t) === 1
        ) {
            echo get_paremiotipus_display($p, escape_html: false) . "\n";
        }
    }
    echo '</pre>';

    echo '<h3>Paremiotipus amb caràcters de guió o guionet no estàndards (ni — ni -)</h3>';
    echo '<pre>';
    $paremiotipus = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $guions = [
        // '-' => [],
        // '—' => [],
        '‑' => [],
        '–' => [],
        '―' => [],
        '─' => [],
    ];
    $guions_keys = array_keys($guions);
    foreach ($paremiotipus as $p) {
        assert(is_string($p));
        foreach ($guions_keys as $guio) {
            if (str_contains($p, $guio)) {
                $guions[$guio][] = $p;
            }
        }
    }
    foreach ($guions as $guio => $guio_array) {
        $count = count($guio_array);
        if ($count === 0) {
            continue;
        }
        echo "<i>Caràcter {$guio}</i>\n";
        foreach ($guio_array as $p) {
            echo get_paremiotipus_display($p, escape_html: false) . "\n";
        }
        echo "\n\n";
    }
    echo '</pre>';

    echo '<h3>Modismes amb caràcters de guió o guionet no estàndards (ni — ni -)</h3>';
    echo '<pre>';
    $paremiotipus = get_db()->query('SELECT `MODISME` FROM `00_PAREMIOTIPUS` ORDER BY `MODISME`')->fetchAll(PDO::FETCH_COLUMN);
    $guions = [
        // '-' => [],
        // '—' => [],
        '‑' => [],
        '–' => [],
        '―' => [],
        '─' => [],
    ];
    $guions_keys = array_keys($guions);
    foreach ($paremiotipus as $p) {
        assert(is_string($p));
        foreach ($guions_keys as $guio) {
            if (str_contains($p, $guio)) {
                $guions[$guio][] = $p;
            }
        }
    }
    foreach ($guions as $guio => $guio_array) {
        $count = count($guio_array);
        if ($count === 0) {
            continue;
        }
        echo "<i>Caràcter {$guio}</i>\n";
        foreach ($guio_array as $p) {
            echo $p . "\n";
        }
        echo "\n\n";
    }
    echo '</pre>';
}

function test_paremiotipus_final(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h3>Paremiotipus que acaben amb caràcters no alfabètics o amb signe de puntuació inusual</h3>';
    echo '<pre>';
    $paremiotipus = get_db()->query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($paremiotipus as $p) {
        assert(is_string($p));
        $t = str_replace(
            ['à', 'è', 'é', 'í', 'ï', 'ò', 'ó', 'ú', 'ü', 'ç'],
            ['a', 'e', 'e', 'i', 'i', 'o', 'o', 'u', 'u', 'c'],
            mb_strtolower($p)
        );
        if (
            preg_match('/[a-z]$/', $t) === 0
            && !str_ends_with($t, '!')
            && !str_ends_with($t, '?')
            && !str_ends_with($t, '»')
            && !str_ends_with($t, '"')
            && !str_ends_with($t, '…')
        ) {
            echo get_paremiotipus_display($p, escape_html: false) . "\n";
        }
    }
    echo '</pre>';
}
