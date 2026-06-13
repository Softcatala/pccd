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

// Init page.
$page = new PageRenderer();

?><!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <title><?php echo $page->getTitle() . ' | PCCD'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="<?php echo COLOR_PRIMARY; ?>">
    <meta property="og:title" content="<?php echo $page->getTitle(); ?>">
    <meta property="og:site_name" content="Paremiologia catalana comparada digital">
    <?php echo $page->renderPageMetaTags(); ?>
    <link rel="icon" href="/favicon.ico">
    <link rel="search" type="application/opensearchdescription+xml" href="/opensearch.xml" title="PCCD">
    <style><?php $page->printCss(); ?></style>
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
            <article<?php echo $page->name === 'search' ? ' data-nosnippet' : ''; ?>>
                <h1><?php echo $page->getTitle(); ?></h1>
                <?php echo $page->mainContent; ?>
            </article>
            <aside aria-label="Informació addicional">
                <?php echo $page->sideBlocks; ?>
                <div class="bloc bloc-contact bloc-white">
                    <p>Ajudeu-nos a millorar</p>
                    <p><a href="mailto:vpamies@gmail.com?subject=PCCD"><img alt="Contacteu-nos" title="Contacteu-nos" width="80" height="44" src="/img/cargol.svg" loading="lazy"></a></p>
                </div>
            </aside>
        </div>
    </main>
    <footer><?php echo $page->renderFooter(); ?></footer>
    <script><?php $page->printJs(); ?></script>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-CP42Y3NK1R"></script>
</body>
</html>
