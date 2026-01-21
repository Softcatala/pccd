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

// Set page metadata.
PageRenderer::setTitle('Llibres de Víctor Pàmies');
PageRenderer::setMetaDescription("Llibres publicats per l'autor de la Paremiologia catalana comparada digital.");

$books = get_books();
echo '<div class="books">';
foreach ($books as $book) {
    echo $book->render(lazy_loading: false);
}
echo '</div>';
