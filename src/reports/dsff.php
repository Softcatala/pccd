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

function test_dsff(): void
{
    require_once __DIR__ . '/../common.php';

    $pccd_dsff_map = [
        '*' => 'Espinal, M. Teresa (2004): Diccionari de sinònims de frases fetes (DSFF), 2a ed. 2006',
        'A-M' => 'Alcover-Moll (1926-1962): Diccionari català-valencià-balear (10 Vol.) [DCVB]',
        'B' => 'Balbastre i Ferrer, Josep (1977): Nou recull de modismes i frases fetes, 6a ed. 1992',
        'P' => 'Peris, Antoni (2001): Diccionari de locucions i frases llatines',
        'R' => 'Riera Jaume, Antoni (1999): Així xerram a Mallorca',
        // The following is similar but not the same work found in DSFF:
        // 'R-M' => 'Raspall-Martí (1984): Diccionari de locucions, 2a ed, 1995'.
        'SP' => 'Perramon i Barnadas, Sever (1979): Proverbis, dites i frases fetes de la llengua catalana, ed. 1983',
    ];

    $titles = [
        '*' => 'no prové de cap obra lexicogràfica (*)',
        'A-M' => 'Alcover, A. M. – F. de B. Moll, Diccionari Català-Valencià-Balear (A-M)',
        'B' => 'Balbastre, J., Nou Recull de Modismes i Frases Fetes. Català-castellà / castellà-català (B)',
        'DIEC1' => "Institut d'Estudis Catalans, Diccionari de la Llengua Catalana (DIEC1)",
        'EC' => 'Enciclopèdia Catalana, Diccionaris (EC)',
        'ECe' => "Enciclopèdia Catalana i Universitat Politècnica de Catalunya, Diccionari d'Economia i Gestió (ECe)",
        'F' => 'Fabra, P., Diccionari General de la Llengua Catalana (F)',
        'Fr' => 'Franquesa, M., Diccionari de Sinònims (Fr)',
        'GEC' => 'Gran Enciclopèdia Catalana (GEC)',
        'P' => 'Peris, A., Diccionari de Locucions i Frases Llatines (P)',
        'PDL' => "Institut d'Estudis Catalans, Portal de Dades Lingüístiques (PDL)",
        'R' => 'Riera Jaume, A., Així Xerram a Mallorca (R)',
        'R-M' => 'Raspall, J. - J. Martí, Diccionari de Locucions i de Frases Fetes (R-M)',
        'SP' => 'Perramón, S., Proverbis, Dites i Frases Fetes de la Llengua Catalana (SP)',
        'T' => 'Termcat (T)',
    ];

    echo '<h3>Frases del DSFF v3 beta sense modisme a la PCCD</h3>';
    echo "<i>La versió digital i en paper del DSFF, la que es devia buidar a la PCCD en el seu moment, es va publicar el 2004. La segona edició del DSFF no contenia cap canvi respecte a la primera; simplement es va etiquetar així per motius de màrqueting. La pàgina <a href=//dsff.uab.cat>https://dsff.uab.cat</a>, llançada el 2018, ja incorporava prop d'un miler de correccions en comparació amb l'edició de 2004. Aquest informe recull frases d'aquests canvis i inclou també contingut de la futura versió de <a href=//dsff.uab.cat>https://dsff.uab.cat</a> que s'està desenvolupant. La futura versió del DSFF, que encara no és pública, contindrà un buidatge selectiu del DCVB, així com correccions diverses. L'informe és per tant per ara preliminar però pot ajudar a identificar omissions, problemes i errors de picatge tant a la PCCD com al DSFF.</i><br>";

    $modismes = get_db()->query('SELECT DISTINCT BINARY LOWER(`MODISME`), ID_FONT FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_KEY_PAIR);
    $modisme_font = get_db()->query('SELECT DISTINCT BINARY CONCAT(LOWER(`MODISME`), "_", ID_FONT) AS `MODISME_FONT`, 1 FROM `00_PAREMIOTIPUS`')->fetchAll(PDO::FETCH_KEY_PAIR);
    $json_content = file_get_contents(__DIR__ . '/dsff_v3beta.txt');
    assert(is_string($json_content));

    $sentences = [];
    $missing_from_source = [];
    $data = json_decode(json: $json_content, associative: true, flags: JSON_THROW_ON_ERROR);
    assert(is_array($data));
    // Generate this set for quick lookups in next reports.
    $dsff_all_sentences = [];
    foreach ($data as $item) {
        assert(is_array($item));
        assert(is_string($item['title']));
        assert(is_string($item['sources']));
        $title = trim($item['title']);
        $title_lc = mb_strtolower($title);
        $dsff_all_sentences[$title_lc] = true;
        if (!isset($modismes[$title_lc])) {
            $sentences[$title] = $item['sources'];
        } else {
            // If it exists, check that the source matches.
            $sources = trim($item['sources']);
            $sources = explode(',', $sources);
            if (!in_array('*', $sources, true)) {
                // Consider DSFF to always be a source.
                $sources[] = '*';
            }
            foreach ($sources as $s) {
                $source = trim($s);
                if (!isset($pccd_dsff_map[$source])) {
                    continue;
                }

                $pccd_source = $pccd_dsff_map[$source];
                if (isset($modisme_font[$title_lc . '_' . $pccd_source])) {
                    continue;
                }

                if (!isset($missing_from_source[$pccd_source])) {
                    $missing_from_source[$pccd_source] = [];
                }
                $missing_from_source[$pccd_source][] = $title;
            }
        }
    }
    // Let's put DSFF source at the end.
    $missing_from_source = array_reverse($missing_from_source, true);

    echo 'La PCCD té actualment ' . format_nombre(count($modismes)) . ' modismes únics.';
    echo ' El DSFF v3 beta té actualment ' . format_nombre(count($data)) . ' frases, de les quals ' . format_nombre(count($sentences)) . ' no existeixen com a modisme a la PCCD.';

    $works = array_fill_keys(array_keys($titles), 0);
    $table = '<table border="1">';
    $table .= '<tr><th>Frase</th><th>Font DSFF</th></tr>';
    foreach ($sentences as $sentence => $source) {
        $source = trim($source);
        if ($source === '') {
            $source = '*';
        }
        $sources = explode(',', $source);
        foreach ($sources as $s) {
            assert(isset($works[trim($s)]));
            $works[trim($s)]++;
        }
        $table .= '<tr>';
        $table .= '<td>' . htmlspecialchars($sentence) . '</td>';
        $table .= '<td>' . $source . '</td>';
        $table .= '</tr>';
    }
    $table .= '</table>';

    echo '<h4>Llista de frases omeses</h4>';
    echo '<details>' . $table . '</details>';
    arsort($works);
    $occurrences_table = '<table border="1">';
    $occurrences_table .= '<tr><th>Font DSFF</th><th>Ocurrències</th></tr>';
    foreach ($works as $source => $count) {
        $occurrences_table .= '<tr>';
        $occurrences_table .= '<td>' . $titles[$source] . '</td>';
        $occurrences_table .= '<td>' . $count . '</td>';
        $occurrences_table .= '</tr>';
    }
    $occurrences_table .= '</table>';

    echo "<h4>Nombre d'omissions per font del DSFF</h4>";
    echo '<details>' . $occurrences_table . '</details>';

    echo '<h3>Frases del DSFF v3 beta que existeixen com a modisme a la PCCD, però no amb la font comuna corresponent</h3>';
    echo '<i>Això pot ser degut a que el DSFF o la PCCD no hagin copiat el modisme literalment. En el cas de la font DSFF, probablement la majoria de vegades és degut a les noves incorporacions.</i>';
    $wrong_source_table = '<table border="1">';
    $wrong_source_table .= '<tr><th>Frase</th><th>Font</th></tr>';
    $count = 0;
    foreach ($missing_from_source as $source => $phrases) {
        foreach ($phrases as $sentence) {
            $wrong_source_table .= '<tr>';
            $wrong_source_table .= "<td>{$sentence}</td><td>{$source}</td>";
            $wrong_source_table .= '<tr>';
            $count++;
        }
    }
    $wrong_source_table .= '</table>';
    echo '<br>Total: ' . format_nombre($count);
    echo '<details>' . $wrong_source_table . '</details>';

    echo '<h3>Modismes de la PCCD provinents del DSFF o de fonts comunes amb el DSFF però que no es troben com a frase al DSFF v3 beta</h3>';
    echo "<i>A més dels motius anteriors, pot ser degut a que el DSFF adapta i normalitza la frase (p. ex. usant sempre el verb ésser, o amb les expressions indefinides usades entre parèntesis), o que el buidatge a la PCCD també va incloure les variants formals i dialectals com a modisme. En el cas de la font DCVB, cal tenir en compte que la incorporació al DSFF no s'ha completat.</i>";
    $fonts_ids = array_values($pccd_dsff_map);
    $placeholders = str_repeat('?,', count($fonts_ids) - 1) . '?';
    $pccd_modismes_stmt = get_db()->prepare("SELECT DISTINCT `MODISME`, `ID_FONT` FROM `00_PAREMIOTIPUS` WHERE `ID_FONT` IN ({$placeholders})");
    $pccd_modismes_stmt->execute($fonts_ids);

    $pccd_modismes = $pccd_modismes_stmt->fetchAll(PDO::FETCH_ASSOC);
    $missing_modismes_table = '<table border="1">';
    $missing_modismes_table .= '<tr><th>Modisme</th><th>Font PCCD</th></tr>';
    $count = 0;
    foreach ($pccd_modismes as $modisme) {
        if ($modisme['MODISME'] === '') {
            continue;
        }

        assert(is_string($modisme['MODISME']));
        assert(is_string($modisme['ID_FONT']));
        if (isset($dsff_all_sentences[mb_strtolower($modisme['MODISME'])])) {
            continue;
        }

        $missing_modismes_table .= '<tr><td>' . htmlspecialchars($modisme['MODISME']) . '</td><td>' . htmlspecialchars($modisme['ID_FONT']) . '</td></tr>';
        $count++;
    }
    $missing_modismes_table .= '</table>';
    echo '<br>Total: ' . format_nombre($count);
    echo '<details>' . $missing_modismes_table . '</details>';
}
