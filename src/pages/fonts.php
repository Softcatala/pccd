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

require __DIR__ . '/fonts_functions.php';

PageRenderer::setTitle('Fonts bibliogràfiques');
PageRenderer::setMetaDescription('Llista de les fonts bibliogràfiques disponibles a la Paremiologia catalana comparada digital.');

echo '<table id="fonts">';
echo '<thead><tr><th scope="col">Autor</th><th scope="col">Any</th><th scope="col">Títol</th><th scope="col" class="registres">Registres</th><th scope="col" class="varietat">Varietat dialectal</th></tr></thead>';
echo '<tbody>';
$obres = get_fonts();
foreach ($obres as $obra) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($obra->Autor) . '</td>';
    echo '<td>' . $obra->Any . '</td>';
    echo '<td><a href="' . get_obra_url($obra->Identificador) . '">' . htmlspecialchars($obra->Títol) . '</a></td>';
    echo '<td>' . $obra->Registres . '</td>';
    echo '<td>' . htmlspecialchars($obra->Varietat_dialectal) . '</td>';
    echo '</tr>';
}
echo '</tbody>';
echo '</table>';
