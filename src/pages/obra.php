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

$obra_title = is_string($_GET['obra']) ? slug_to_name($_GET['obra']) : '';
$obra = get_obra($obra_title);

if ($obra === false) {
    return_404_and_exit();
}

$canonical_url = get_obra_url($obra->Identificador, absolute: true);

if (!str_starts_with(get_request_uri(), '/obra/')) {
    // Redirect old URLs to the new ones.
    header("Location: {$canonical_url}", response_code: 301);

    exit;
}

PageRenderer::setCanonicalUrl($canonical_url);
PageRenderer::setTitle(htmlspecialchars($obra->Títol));

$is_book = $obra->ISBN !== '';
if ($is_book) {
    PageRenderer::setOgType(OgType::BOOK);
    echo '<div class="row" vocab="http://schema.org/" typeof="Book">';
} else {
    echo '<div class="row" vocab="http://schema.org/" typeof="Thing">';
}

if (is_file(__DIR__ . '/../../docroot/img/obres/' . $obra->Imatge)) {
    PageRenderer::setMetaImage('https://pccd.dites.cat/img/obres/' . rawurlencode($obra->Imatge));

    echo '<figure class="col-image">';
    echo render_image_tags(
        file_name: $obra->Imatge,
        path: '/img/obres/',
        alt_text: $is_book ? 'Coberta' : $obra->Títol,
        width: $obra->WIDTH,
        height: $obra->HEIGHT,
        preload: true,
        preload_media: '(min-width: 576px)'
    );
    echo '</figure>';
}

echo '<div class="col-work text-break">';
echo '<dl>';
if ($obra->Autor !== '') {
    echo '<dt>Autor:</dt>';
    echo '<dd property="author" typeof="Person">';
    echo '<span property="name">' . htmlspecialchars($obra->Autor) . '</span>';
    echo '</dd>';
}
if ($obra->Any !== '') {
    echo '<dt>Any de publicació:</dt>';
    echo '<dd property="datePublished">' . htmlspecialchars($obra->Any) . '</dd>';
}
if ($obra->ISBN !== '') {
    $isbn = htmlspecialchars($obra->ISBN);
    echo '<dt>ISBN:</dt>';
    echo '<dd>';
    if (isbn_is_valid($isbn)) {
        $isbn_url = 'https://ca.wikipedia.org/wiki/Especial:Fonts_bibliogr%C3%A0fiques?isbn=' . $isbn;
        echo '<a property="isbn" title="Cerqueu l\'obra a llibreries i biblioteques" href="' . $isbn_url . '" class="external" target="_blank" rel="noopener">';
        echo $isbn;
        echo '</a>';
    } else {
        echo $isbn;
    }
    echo '</dd>';
}
if ($obra->Editorial !== '' && $obra->Editorial !== 'Web') {
    echo '<dt>Editorial:</dt>';
    echo '<dd property="publisher" typeof="Organization">';
    echo '<span property="name">' . htmlspecialchars($obra->Editorial) . '</span>';
    echo '</dd>';
}
if ($obra->Edició !== '') {
    echo '<dt>Edició:</dt>';
    echo '<dd property="bookEdition">' . htmlspecialchars($obra->Edició) . '</dd>';
}
if ($obra->Any_edició !== '' && $obra->Any_edició !== '0') {
    echo "<dt>Any de l'edició:</dt>";
    echo '<dd property="copyrightYear">' . $obra->Any_edició . '</dd>';
}
if ($obra->Municipi !== '') {
    echo '<dt>Municipi:</dt>';
    echo '<dd property="locationCreated" typeof="Place">';
    echo '<span property="name">' . htmlspecialchars($obra->Municipi) . '</span>';
    echo '</dd>';
}
if ($obra->Collecció !== '') {
    echo '<dt>Col·lecció:</dt>';
    echo '<dd>' . htmlspecialchars($obra->Collecció) . '</dd>';
}
if ($obra->Núm_collecció !== '') {
    echo '<dt>Núm. de la col·lecció:</dt>';
    echo '<dd>' . htmlspecialchars($obra->Núm_collecció) . '</dd>';
}
if ($obra->Idioma !== '') {
    echo '<dt>Idioma:</dt>';
    echo '<dd property="inLanguage">' . htmlspecialchars($obra->Idioma) . '</dd>';
}
if ($obra->Varietat_dialectal !== '') {
    echo '<dt>Varietat dialectal:</dt>';
    echo '<dd>' . htmlspecialchars($obra->Varietat_dialectal) . '</dd>';
}
if ($obra->Pàgines !== '' && $obra->Pàgines !== '0') {
    echo '<dt>Núm. de pàgines:</dt>';
    echo '<dd property="numberOfPages">' . format_nombre($obra->Pàgines) . '</dd>';
}
if ($obra->Data_compra !== '') {
    $date = DateTime::createFromFormat('Y-m-d', $obra->Data_compra);
    echo '<dt>Data de compra:</dt>';
    echo '<dd>';
    echo $date !== false ? $date->format('d/m/Y') : htmlspecialchars($obra->Data_compra);
    echo '</dd>';
}
if ($obra->Lloc_compra !== '') {
    echo '<dt>Lloc de compra:</dt>';
    echo '<dd>' . htmlspecialchars($obra->Lloc_compra) . '</dd>';
}
if ($obra->Preu !== '' && $obra->Preu !== '0') {
    echo '<dt>Preu de compra:</dt>';
    echo '<dd>' . format_preu($obra->Preu) . '&nbsp;€</dd>';
}
if ($obra->URL !== '') {
    echo '<dt hidden>Enllaç:</dt>';
    echo '<dd>' . html_escape_and_link_urls(text: $obra->URL, property: 'url') . '</dd>';
}
if ($obra->Observacions !== '') {
    echo '<dt>Observacions:</dt>';
    echo '<dd property="description">' . html_escape_and_link_urls(prepare_field($obra->Observacions, escape_html: false)) . '</dd>';
    PageRenderer::setMetaDescription(prepare_field($obra->Observacions));
}
echo '</dl>';

$collected_count = get_paremiotipus_count_by_font($obra->Identificador);
echo '<div class="footer">';
echo 'Aquesta obra té ' . format_nombre($obra->Registres) . ($obra->Registres === '1' ? ' fitxa' : ' fitxes');
echo ' a la base de dades, de les quals ' . format_nombre($collected_count);
echo ($collected_count === 1 ? ' està recollida' : ' estan recollides') . ' en aquest web.';
echo '</div>';

echo '</div></div>';
