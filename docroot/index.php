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

declare(strict_types=1);

const NONCE_LENGTH = 18;

require __DIR__ . '/../src/third_party/urlLinker.php';

require __DIR__ . '/../src/common.php';

// Redirect to the homepage any request where the URL contains 'index.php'.
if (str_contains(get_request_uri(), 'index.php')) {
    header('Location: https://pccd.dites.cat', true, 302);

    exit;
}

header('Cache-Control: public, s-maxage=31536000, max-age=300');

// TODO: remove `unsafe-inline` (drops support for old browsers).
$nonce = base64_encode(random_bytes(NONCE_LENGTH));
header(
    "Content-Security-Policy: default-src 'self'; "
    . "base-uri 'none'; "
    . "connect-src 'self' https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com; "
    . "frame-ancestors 'none'; "
    . "img-src 'self' https://*.google-analytics.com https://*.googletagmanager.com; "
    . "object-src 'none'; "
    . "script-src 'self' https://*.googletagmanager.com; "
    . "style-src 'self' 'nonce-{$nonce}' 'unsafe-inline'"
);

// Build page content in advance, and populate some variables above.
$page_name = get_page_name();
$main_content = build_main_content($page_name);

if (get_page_title() === '') {
    set_page_title('Paremiologia catalana comparada digital');
}

?><!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <title><?php echo format_html_title(get_page_title(), 'PCCD'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2b5797">
    <meta property="og:title" content="<?php echo format_html_title(get_page_title()); ?>">
    <meta property="og:site_name" content="Paremiologia catalana comparada digital">
<?php
if ($page_name === 'search') {
    if (!isset($_GET['cerca']) || $_GET['cerca'] === '') {
        // Set canonical URL in the homepage.
        set_canonical_url('https://pccd.dites.cat');
    } else {
        // Do not index the rest of result pages.
        echo '<meta name="robots" content="noindex">';
    }

    // Provide nice-to-have social metadata for the homepage and search pages.
    echo '<meta name="twitter:card" content="summary_large_image">';
    echo '<meta property="og:type" content="website">';
    // See https://stackoverflow.com/q/71087872/1391963.
    echo '<meta name="twitter:image" property="og:image" content="https://pccd.dites.cat/img/screenshot.png">';
} else {
    // Set og:type article for all other pages.
    echo '<meta property="og:type" content="article">';
}

// Canonical may be set above or in paremiotipus and obra pages.
if (get_canonical_url() !== '') {
    echo '<link rel="canonical" href="' . get_canonical_url() . '">';
}

// Meta description may be set when building main content.
if (get_meta_description() !== '') {
    echo '<meta name="description" property="og:description" content="' . get_meta_description() . '">';
}

// Meta image may be set in paremiotipus and obra pages.
if (get_meta_image() !== '') {
    echo '<meta name="twitter:card" content="summary">';
    // See https://stackoverflow.com/q/71087872/1391963.
    echo '<meta name="twitter:image" property="og:image" content="' . get_meta_image() . '">';
}

// og:audio URL may be set in paremiotipus pages.
if (get_og_audio_url() !== '') {
    echo '<meta property="og:audio" content="' . get_og_audio_url() . '">';
}

// URLs to prefetch may be set in search pages.
foreach (get_prefetch_urls() as $url => $type) {
    echo '<link rel="prefetch" href="' . $url . '" as="' . $type . '">';
}
?>
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="search" type="application/opensearchdescription+xml" href="/opensearch.xml" title="PCCD">
    <style nonce="<?php echo $nonce; ?>">
<?php
require __DIR__ . '/css/base.min.css';

/** @psalm-suppress UnresolvableInclude */
include __DIR__ . "/css/{$page_name}.min.css";
?>
    </style>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-CP42Y3NK1R"></script>
</head>
<body>
    <nav class="navbar navbar-expand-md">
        <div class="container-md">
            <a href="/" class="navbar-brand" aria-label="PCCD"><span>Paremiologia catalana comparada digital</span></a>
            <button id="navbar-toggle" type="button">
                <span class="navbar-toggle-icon">
                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                        <path fill="white" d="M3 9.5a1.5 1.5 0 1 1 0-3a1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3a1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3a1.5 1.5 0 0 1 0 3z"/>
                    </svg>
                </span>
                <span class="sr-only">Desplega el menú</span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="menu">
                <div class="navbar-nav">
                    <a class="nav-item nav-link" href="/projecte">Projecte</a>
                    <a class="nav-item nav-link" href="/"<?php echo $page_name !== 'search' ? ' title="Ves a la pàgina principal (Ctrl /)"' : ''; ?>>Cerca</a>
                    <a class="nav-item nav-link" href="/instruccions">Instruccions d'ús</a>
                    <a class="nav-item nav-link" href="/credits">Crèdits</a>
                </div>
            </div>
        </div>
    </nav>
    <div id="contingut" class="container-md">
        <div class="row">
            <main class="col-md-9"<?php echo $page_name === 'search' ? ' data-nosnippet' : ''; ?>>
<?php
// Search and obra pages have a slightly different template.
if ($page_name === 'obra' || $page_name === 'search') {
    echo $main_content;
} else {
    echo '<article>';
    echo '<h1>' . get_page_title() . '</h1>';
    echo $main_content;
    echo '</article>';
}
?>
            </main>
            <aside class="col-md">
<?php

// Side blocks are populated in paremiotipus pages.
echo get_side_blocks();

if ($page_name === 'search') {
    $random_paremiotipus = get_random_top100_paremiotipus();
    echo '<div class="bloc bloc-top-paremies" data-nosnippet>';
    echo '<p>';
    echo '«<a href="' . get_paremiotipus_url($random_paremiotipus) . '">';
    echo get_paremiotipus_display($random_paremiotipus);
    echo '</a>»';
    echo '</p>';
    echo '<p class="peu"><a href="/top100">Les 100 parèmies més citades</a></p>';
    echo '</div>';

    $random_book = get_random_book();
    echo '<div class="bloc bloc-llibres">';
    echo '<p><a href="/llibres">Llibres de l\'autor</a></p>';
    echo '<a href="' . $random_book['URL'] . '" title="' . htmlspecialchars($random_book['Títol']) . '">';
    echo get_image_tags(
        $random_book['Imatge'],
        '/img/obres/',
        $random_book['Títol'],
        $random_book['WIDTH'],
        $random_book['HEIGHT'],
        false
    );
    echo '</a>';
    echo '</div>';
} else {
    $random_paremiotipus = get_random_top10000_paremiotipus();
    echo '<div class="bloc d-none d-md-block" data-nosnippet>';
    echo '<p>';
    echo '«<a title="Parèmia aleatòria" href="' . get_paremiotipus_url($random_paremiotipus) . '">';
    echo get_paremiotipus_display($random_paremiotipus);
    echo '</a>»';
    echo '</p>';
    echo '<p class="peu">Les 10.000 parèmies més citades</p>';
    echo '</div>';
}
?>
                <div class="bloc bloc-credits bloc-2">
                    <p>Un projecte de:</p>
                    <p><a class="credits" href="http://www.dites.cat" title="www.dites.cat">dites.cat</a></p>
                    <p><a href="https://www.softcatala.org" title="Softcatalà"><img loading="lazy" alt="Logo de Softcatalà" width="120" height="80" src="/img/logo-softcatala.svg"></a></p>
                </div>
                <div class="bloc bloc-2">
                    <p>Ajudeu-nos a millorar</p>
                    <p><a href="mailto:vpamies@gmail.com?subject=PCCD"><img loading="lazy" alt="Email de contacte" title="Contacteu-nos" width="80" height="44" src="/img/cargol.svg"></a></p>
                </div>
            </aside>
        </div>
        <footer>
            <p><?php echo format_nombre(get_n_modismes()); ?> fitxes, corresponents a <?php echo format_nombre(get_n_paremiotipus()); ?> paremiotipus, de <?php echo format_nombre(get_n_fonts()); ?> fonts. Última actualització: <?php require __DIR__ . '/../tmp/date.txt'; ?></p>
            <p>© Víctor Pàmies i Riudor, 2020-2023.</p>
        </footer>
    </div>
    <div id="snackbar" class="d-none">
        <div class="snackbar-snack" role="alert">
            <div class="snackbar-inner">
                <div class="snackbar-message">Aquest lloc web fa servir galetes de Google per analitzar el trànsit.</div>
                <button id="snackbar-action" type="button">D'acord</button>
            </div>
        </div>
    </div>
    <script async src="/js/script.min.js"></script>
</body>
</html>
