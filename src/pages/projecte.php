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
PageRenderer::setTitle('Projecte');
PageRenderer::setMetaDescription('Versió en línia del buidatge de fonts fraseològiques, escrites, orals o digitals del paremiòleg català Víctor Pàmies i Riudor (Barcelona, 1963).');
?>
<p>La <em>paremiologia catalana comparada digital</em> (PCCD) és la versió en línia del buidatge asistemàtic, iniciat a partir del 1997, de totes les fonts fraseològiques, escrites, orals o digitals, que conformen la base de dades de treball i investigació del paremiòleg català Víctor Pàmies i Riudor (Barcelona, 1963).</p>

<p>És, doncs, la passió de tota una vida dedicada a cercar i recopilar parèmies, per a digitalitzar-les i facilitar-ne la consulta.</p>

<p>La base inicial eren fitxers de dades en dBase IV, migrats posteriorment a Microsoft Access. Estem parlant d'una base actual de més d'un milió de registres, dels quals ja n'hem pogut abocar <?php echo format_nombre(get_modisme_count()); ?> en aquesta interfície de consulta.</p>

<p>A partir de la determinació d'un paremiotipus de base per a cada unitat fraseològica, extreta de fonts molt diverses (amb molt diversos estils i informacions), com en un trencaclosques gegantí, hem hagut de fer encaixar totes les peces. Per tant, en cada moment, cal determinar quines expressions són equiparables en català i, sovint també, determinar quins equivalents trobem en altres llengües.</p>

<p>Hem previst un abocament seqüencial d'informació i de dades.</p>

<p>Així, en una primera fase s'ha abocat la base principal amb la informació continguda de cada font original buidada, la qual cosa permet determinar, de cada dita, una localització completa per a recuperar-la.</p>

<p>També hem implementat informació addicional respecte de les fonts buidades (per poder fer una fitxa bibliogràfica completa, amb imatge de la coberta inclosa), com ara sinònims, equivalents idiomàtics, explicacions, exemples d'ús o etimologies diverses i, finalment, informació rellevant pel que fa a l'origen de la dita i a materials audiovisuals digitalitzats (imatges, àudios i vídeos, principalment).</p>

<p>En fases posteriors volem incorporar un arbre temàtic, per classificar les parèmies, i habilitar un refranyer multilingüe que, molt segurament, disposarà d'un aplicatiu propi a banda.</p>
