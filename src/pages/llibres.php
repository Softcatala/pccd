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

PageRenderer::setTitle('Llibres de Víctor Pàmies');
PageRenderer::setMetaDescription("Llibres publicats per l'autor de la Paremiologia catalana comparada digital.");

echo '<div class="books">';
foreach (get_books() as $book) {
    echo $book->render(lazy_loading: false);
}
echo '</div>';
