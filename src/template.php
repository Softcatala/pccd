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

// Prepare data for the template.
$page_name = get_page_name();
// These functions are called in advance so they can set some global
// variables early.
$main_content = render_main_content($page_name);
$side_blocks = render_side_blocks($page_name);

?><!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <title><?php echo format_html_title(get_page_title(), 'PCCD'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2b5797">
    <meta property="og:title" content="<?php echo format_html_title(get_page_title()); ?>">
    <meta property="og:site_name" content="Paremiologia catalana comparada digital">
    <?php echo render_page_meta_tags($page_name); ?>
    <link rel="icon" href="/favicon.ico">
    <link rel="search" type="application/opensearchdescription+xml" href="/opensearch.xml" title="PCCD">
    <style>
<?php
require __DIR__ . '/../docroot/css/base.min.css';

// If the page has page-specific CSS, include it.
@include __DIR__ . "/../docroot/css/pages/{$page_name}.min.css";
?>
</style>
</head>
<body>
    <header>
        <div>
            <a href="/" class="brand">Paremiologia catalana comparada digital</a>
            <nav aria-label="Menú principal">
                <a href="/projecte">Projecte</a>
                <a href="/">Cerca</a>
                <a href="/instruccions">Instruccions</a>
                <a href="/fonts">Fonts</a>
                <a href="/credits">Crèdits</a>
            </nav>
        </div>
    </header>
    <main>
        <div class="row">
            <article<?php echo $page_name === 'search' ? ' data-nosnippet' : ''; ?>>
                <h1><?php echo get_page_title(); ?></h1>
                <?php echo $main_content; ?>
            </article>
            <aside aria-label="Informació addicional">
                <?php echo $side_blocks; ?>
                <div class="bloc bloc-contact bloc-white">
                    <p>Ajudeu-nos a millorar</p>
                    <p><a href="mailto:vpamies@gmail.com?subject=PCCD"><img alt="Contacteu-nos" title="Contacteu-nos" width="80" height="44" src="/img/cargol.svg" loading="lazy"></a></p>
                </div>
            </aside>
        </div>
    </main>
    <footer>
        <p><?php echo format_nombre(get_n_modismes()); ?>&nbsp;fitxes, corresponents a <?php echo format_nombre(get_n_paremiotipus()); ?>&nbsp;paremiotipus, recollides de <?php echo format_nombre(get_n_fonts()); ?>&nbsp;fonts i <?php echo format_nombre(get_n_informants()); ?>&nbsp;informants. Última actualització: <?php require __DIR__ . '/../tmp/db_date.txt'; ?></p>
        <p>© Víctor Pàmies i Riudor, 2020-2025.</p>
    </footer>
    <div id="cookie-banner" hidden>
        <div role="alert">
            <div>Aquest lloc web fa servir galetes de Google per analitzar el trànsit.</div>
            <button type="button">D'acord</button>
        </div>
    </div>
    <script>
<?php
if (in_array($page_name, ['search', 'paremiotipus', 'fonts'], true)) {
    require __DIR__ . "/../docroot/js/pages/{$page_name}.min.js";
}

require __DIR__ . '/../docroot/js/app.min.js';
?>
    </script>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-CP42Y3NK1R"></script>
</body>
</html>
