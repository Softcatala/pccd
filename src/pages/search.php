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

require __DIR__ . '/search_functions.php';

PageRenderer::setMetaDescription("La PCCD dona accés a la consulta d'un gran ventall de fonts fraseològiques sobre parèmies en general (locucions, frases fetes, refranys, proverbis, citacions, etc.)");
PageRenderer::setMetaImage('https://pccd.dites.cat/img/screenshot.png');

$current_page = get_search_page_number();
$results_per_page = get_search_page_limit();
$offset = get_search_page_offset($current_page, $results_per_page);
$search_mode = get_search_mode();
$search_normalized = get_search_normalized($search_mode);
$search_clean = get_search_clean();

$where_clause = '';
$arguments = [];
if ($search_normalized === '' && !isset($_GET['font'])) {
    // If the search is empty, we are in the home.
    PageRenderer::setTitle('Paremiologia catalana comparada digital');
    PageRenderer::setCanonicalUrl('https://pccd.dites.cat');
    PageRenderer::setOgType('website');
    $total = get_n_paremiotipus();
} else {
    // Otherwise, we are in a search page.
    PageRenderer::setTitle('Cerca «' . $search_clean . '»');
    PageRenderer::setOgType('');
    $arguments = build_search_query($search_normalized, $search_mode, $where_clause);
    $total = get_n_results($where_clause, $arguments);
}

$number_of_pages = get_search_pages_number($total, $results_per_page);
if ($number_of_pages > 1 && $search_normalized !== '') {
    // Show the page number in the title too.
    PageRenderer::setTitle('Cerca «' . $search_clean . "», pàgina {$current_page}");
}
?>
<form method="get" role="search">
    <div class="filters">
        <div class="row">
            <div class="mode">
                <select name="mode" aria-label="Mode de cerca">
                    <option value="">conté</option>
                    <option<?php echo $search_mode === 'comença' ? ' selected' : ''; ?> value="comença">comença per</option>
                    <option<?php echo $search_mode === 'acaba' ? ' selected' : ''; ?> value="acaba">acaba en</option>
                    <option<?php echo $search_mode === 'coincident' ? ' selected' : ''; ?> value="coincident">coincident</option>
                </select>
            </div>
            <div class="input">
                <input type="search" name="cerca" autocapitalize="off" autocomplete="off" autofocus value="<?php echo $search_clean; ?>" placeholder="Introduïu un o diversos termes" aria-label="Introduïu un o diversos termes" pattern=".*[a-zA-Z]+.*" required>
                <button type="submit" aria-label="Cerca">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14"/></svg>
                </button>
            </div>
        </div>
        <div class="row">
            <div class="label">Inclou en la cerca:</div>
            <div class="options">
                <div title="Variants del paremiotipus">
                    <input type="checkbox" name="variant" id="variant" value=""<?php echo checkbox_checked('variant') ? ' checked' : ''; ?>>
                    <label for="variant">variants</label>
                </div>
                <div title="Expressions sinònimes">
                    <input type="checkbox" name="sinonim" id="sinonim" value=""<?php echo checkbox_checked('sinonim') ? ' checked' : ''; ?>>
                    <label for="sinonim">sinònims</label>
                </div>
                <div title="Equivalents en altres llengües">
                    <input type="checkbox" name="equivalent" id="equivalent" value=""<?php echo checkbox_checked('equivalent') ? ' checked' : ''; ?>>
                    <label for="equivalent">altres idiomes</label>
                </div>
            </div>
        </div>
    </div>
<?php

$output = '';
if ($total > 0) {
    if ($search_normalized !== '') {
        $output .= '<p class="text-break">';
        $output .= render_search_summary(
            offset: $offset,
            results_per_page: $results_per_page,
            total: $total,
            search_string: $search_clean
        );
        $output .= '</p>';
    }

    $paremiotipus = get_paremiotipus_search_results(
        where_clause: $where_clause,
        arguments: $arguments,
        limit: $results_per_page,
        offset: $offset
    );
    $output .= '<ol>';
    foreach ($paremiotipus as $p) {
        $output .= '<li><a href="' . get_paremiotipus_url($p) . '">' . get_paremiotipus_display($p) . '</a></li>';
    }
    $output .= '</ol>';
} else {
    $output .= '<p>';
    $output .= "No s'ha trobat cap resultat coincident amb";
    $output .= ' <span class="text-monospace text-break">' . $search_clean . '</span>.';
    $output .= '</p>';
}

$output .= '<div class="pager">';
// Only show pagination links if there is more than one page.
if ($number_of_pages > 1) {
    $output .= render_search_pager($current_page, $number_of_pages);
}
$output .= '<select name="mostra" aria-label="Nombre de resultats per pàgina">';
$output .= '<option value="10">10</option>';
$output .= '<option' . ($results_per_page === 15 ? ' selected' : '') . ' value="15">15</option>';
$output .= '<option' . ($results_per_page === 25 ? ' selected' : '') . ' value="25">25</option>';
$output .= '<option' . ($results_per_page === 50 ? ' selected' : '') . ' value="50">50</option>';
$output .= '</select>';
$output .= '</div>';
$output .= '</form>';

echo $output;
