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
PageRenderer::setOgType(OgType::WEBSITE);

$search_query_input_clean = get_search_query_input_clean();
$is_homepage = $search_query_input_clean === '' && !isset($_GET['font']);

$where_clause = '';
$arguments = [];
if ($is_homepage) {
    PageRenderer::setTitle('Paremiologia catalana comparada digital');
    PageRenderer::setCanonicalUrl('https://pccd.dites.cat');
    $result_count = get_paremiotipus_count();
} else {
    // Otherwise, we are in a search page.
    PageRenderer::setTitle('Cerca «' . $search_query_input_clean . '»');
    [$where_clause, $arguments] = build_search_sql_query();
    $result_count = get_result_count($where_clause, $arguments);
}

$pagination_limit = get_search_pagination_limit();
$page_count = (int) ceil($result_count / $pagination_limit);
$current_page_number = get_search_page_number();
if (!$is_homepage && $page_count > 1) {
    // Show the page number in the title too.
    PageRenderer::setTitle('Cerca «' . $search_query_input_clean . "», pàgina {$current_page_number}");
}
?>
<form method="get" role="search">
    <div class="filters">
        <div class="row">
            <div class="mode"><?php echo render_search_mode_selector(); ?></div>
            <div class="input">
                <input type="search" name="cerca" autocapitalize="off" autocomplete="off" autofocus value="<?php echo $search_query_input_clean; ?>" placeholder="Introduïu un o diversos termes" aria-label="Introduïu un o diversos termes" pattern=".*[a-zA-Z]+.*" required>
                <button type="submit" aria-label="Cerca">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path fill="currentColor" d="M15.5 14h-.8l-.3-.3A6.5 6.5 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.6 0 3-.6 4.2-1.6l.3.3v.8l5 5 1.5-1.5zm-6 0a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9"/></svg>
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

if ($result_count > 0) {
    $offset = ($current_page_number - 1) * $pagination_limit;
    if (!$is_homepage) {
        echo '<p>';
        echo render_search_summary(
            offset: $offset,
            results_per_page: $pagination_limit,
            result_count: $result_count,
            search_query: $search_query_input_clean
        );
        echo '</p>';
    }

    $paremiotipus = get_paremiotipus_search_results(
        where_clause: $where_clause,
        arguments: $arguments,
        limit: $pagination_limit,
        offset: $offset
    );
    echo '<ol>';
    foreach ($paremiotipus as $p) {
        echo '<li><a href="' . get_paremiotipus_url($p) . '">' . get_paremiotipus_display($p) . '</a></li>';
    }
    echo '</ol>';
} else {
    echo '<p>';
    echo "No s'ha trobat cap resultat coincident amb";
    echo ' <span class="text-monospace text-break">' . $search_query_input_clean . '</span>.';
    echo '</p>';
}

echo '<div class="pager">';
echo render_search_pager($current_page_number, $page_count);
echo render_results_per_page_selector($pagination_limit);
echo '</div>';
echo '</form>';
