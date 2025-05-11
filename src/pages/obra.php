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

require __DIR__ . '/obra_functions.php';

$request_uri = get_request_uri();
$obra_title = is_string($_GET['obra']) ? path_to_name($_GET['obra']) : '';
$obra = get_obra($obra_title);
if ($obra === false) {
    // If no match could be found, return an HTTP 404 page.
    error_log("Error: entry not found for URL: {$request_uri}");
    return_404_and_exit();
}

$canonical_url = get_obra_url($obra->Identificador, true);

// Redirect old URLs to the new ones.
if (!str_starts_with($request_uri, '/obra/')) {
    header("Location: {$canonical_url}", response_code: 301);

    exit;
}

PageRenderer::setCanonicalUrl($canonical_url);
PageRenderer::setTitle(htmlspecialchars($obra->Títol));

$is_book = $obra->ISBN !== '';
if ($is_book) {
    PageRenderer::setOgType(OgType::BOOK);
    $output = '<div class="row" vocab="http://schema.org/" typeof="Book">';
} else {
    $output = '<div class="row" vocab="http://schema.org/" typeof="Thing">';
}

if (is_file(__DIR__ . '/../../docroot/img/obres/' . $obra->Imatge)) {
    PageRenderer::setMetaImage('https://pccd.dites.cat/img/obres/' . rawurlencode($obra->Imatge));

    $output .= '<figure class="col-image">';
    $output .= render_image_tags(
        file_name: $obra->Imatge,
        path: '/img/obres/',
        alt_text: $is_book ? 'Coberta' : $obra->Títol,
        width: $obra->WIDTH,
        height: $obra->HEIGHT,
        preload: true,
        preload_media: '(min-width: 576px)'
    );
    $output .= '</figure>';
}

$output .= '<div class="col-work text-break">';
$output .= '<dl>';
if ($obra->Autor !== '') {
    $output .= '<dt>Autor:</dt>';
    $output .= '<dd property="author" typeof="Person">';
    $output .= '<span property="name">' . htmlspecialchars($obra->Autor) . '</span>';
    $output .= '</dd>';
}
if ($obra->Any !== '') {
    $output .= '<dt>Any de publicació:</dt>';
    $output .= '<dd property="datePublished">' . htmlspecialchars($obra->Any) . '</dd>';
}
if ($obra->ISBN !== '') {
    $isbn = htmlspecialchars($obra->ISBN);
    $output .= '<dt>ISBN:</dt>';
    $output .= '<dd>';
    if (isbn_is_valid($isbn)) {
        $isbn_url = 'https://ca.wikipedia.org/wiki/Especial:Fonts_bibliogr%C3%A0fiques?isbn=' . $isbn;
        $output .= '<a property="isbn" title="Cerqueu l\'obra a llibreries i biblioteques" href="' . $isbn_url . '" class="external" target="_blank" rel="noopener">';
        $output .= $isbn;
        $output .= '</a>';
    } else {
        $output .= $isbn;
    }
    $output .= '</dd>';
}
if ($obra->Editorial !== '' && $obra->Editorial !== 'Web') {
    $output .= '<dt>Editorial:</dt>';
    $output .= '<dd property="publisher" typeof="Organization">';
    $output .= '<span property="name">' . htmlspecialchars($obra->Editorial) . '</span>';
    $output .= '</dd>';
}
if ($obra->Edició !== '') {
    $output .= '<dt>Edició:</dt>';
    $output .= '<dd property="bookEdition">' . htmlspecialchars($obra->Edició) . '</dd>';
}
if ($obra->Any_edició !== '' && $obra->Any_edició !== '0') {
    $output .= "<dt>Any de l'edició:</dt>";
    $output .= '<dd property="copyrightYear">' . $obra->Any_edició . '</dd>';
}
if ($obra->Municipi !== '') {
    $output .= '<dt>Municipi:</dt>';
    $output .= '<dd property="locationCreated" typeof="Place">';
    $output .= '<span property="name">' . htmlspecialchars($obra->Municipi) . '</span>';
    $output .= '</dd>';
}
if ($obra->Collecció !== '') {
    $output .= '<dt>Col·lecció:</dt>';
    $output .= '<dd>' . htmlspecialchars($obra->Collecció) . '</dd>';
}
if ($obra->Núm_collecció !== '') {
    $output .= '<dt>Núm. de la col·lecció:</dt>';
    $output .= '<dd>' . htmlspecialchars($obra->Núm_collecció) . '</dd>';
}
if ($obra->Idioma !== '') {
    $output .= '<dt>Idioma:</dt>';
    $output .= '<dd property="inLanguage">' . htmlspecialchars($obra->Idioma) . '</dd>';
}
if ($obra->Varietat_dialectal !== '') {
    $output .= '<dt>Varietat dialectal:</dt>';
    $output .= '<dd>' . htmlspecialchars($obra->Varietat_dialectal) . '</dd>';
}
if ($obra->Pàgines !== '' && $obra->Pàgines !== '0') {
    $output .= '<dt>Núm. de pàgines:</dt>';
    $output .= '<dd property="numberOfPages">' . format_nombre($obra->Pàgines) . '</dd>';
}
if ($obra->Data_compra !== '') {
    $date = DateTime::createFromFormat('Y-m-d', $obra->Data_compra);
    $output .= '<dt>Data de compra:</dt>';
    $output .= '<dd>';
    $output .= $date !== false ? $date->format('d/m/Y') : htmlspecialchars($obra->Data_compra);
    $output .= '</dd>';
}
if ($obra->Lloc_compra !== '') {
    $output .= '<dt>Lloc de compra:</dt>';
    $output .= '<dd>' . htmlspecialchars($obra->Lloc_compra) . '</dd>';
}
if ($obra->Preu !== '' && $obra->Preu !== '0') {
    $output .= '<dt>Preu de compra:</dt>';
    $output .= '<dd>' . format_preu($obra->Preu) . '&nbsp;€</dd>';
}
if ($obra->URL !== '') {
    $output .= '<dt hidden>Enllaç:</dt>';
    $output .= '<dd>' . html_escape_and_link_urls(text: $obra->URL, property: 'url') . '</dd>';
}
if ($obra->Observacions !== '') {
    $output .= '<dt>Observacions:</dt>';
    $output .= '<dd property="description">' . html_escape_and_link_urls(ct($obra->Observacions, escape_html: false)) . '</dd>';
    PageRenderer::setMetaDescription(ct($obra->Observacions));
}
$output .= '</dl>';

$n_recollides = get_paremiotipus_count_by_font($obra->Identificador);
$output .= '<div class="footer">';
$output .= 'Aquesta obra té ' . format_nombre($obra->Registres) . ($obra->Registres === '1' ? ' fitxa' : ' fitxes');
$output .= ' a la base de dades, de les quals ' . format_nombre($n_recollides);
$output .= ($n_recollides === 1 ? ' està recollida' : ' estan recollides') . ' en aquest web.';
$output .= '</div>';

$output .= '</div></div>';
echo $output;
