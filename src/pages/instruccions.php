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
PageRenderer::setTitle("Instruccions d'ús");
PageRenderer::setMetaDescription("Instruccions d'ús de la Paremiologia catalana comparada digital.");
?>
<p>El cos principal de cerca fa una cerca simple en el camp de paremiotipus. Un paremiotipus és una unitat fraseològica (o parèmia) que hem pres com a principal de tot un seguit de variants formals, cronològiques o dialectals.</p>

<p>La cerca retorna l'enllaç a la fitxa dels resultats en pantalles configurables de 10, 15, 25 o 50 resultats. Al peu de la cerca apareixen el nombre total de pantalles que responen a la cerca feta.</p>

<p>Si accedim a qualsevol fitxa, sota del paremiotipus seleccionat hi ha les dades estadístiques de nombre total de recurrències i nombre de variants. A continuació, mostra les diferents variants, amb indicació del nombre de fonts i de l'any de datació més antic, i les diverses fonts per a cada variant.</p>

<p>De cada font mostra aquells camps que són plens (sinònim, equivalent, lloc, explicació, exemples) i tots els camps que permeten identificar la font i on apareix la parèmia en qüestió (autor, any, editorial, títol obra, apartat, pàgina, etc.).</p>

<p>Quan la font citada té fitxa bibliogràfica es mostra amb enllaç, que porta a una nova pàgina amb la coberta del llibre (o font escrita o digital) i una breu fitxa bibliogràfica. Hi consta, quan el sé, el preu del llibre, l'ISBN, l'editorial, l'edició, la col·lecció, el nombre de pàgines i un breu comentari.</p>

<p>Així mateix, en alguns paremiotipus he trobat imatges que representen aquella parèmia. Poden ser il·lustracions de llibres, rajoles, dibuixos, samarretes, bosses de mà, tasses, revistes antigues… Apareix una miniatura a la banda dreta que enllaça amb la font original (si és en línia) i amb anotació de totes les dades possibles per identificar l'autor i l'obra.</p>

<h2>Cerca</h2>

<p>Hem intentat que les opcions de cerca siguin diverses i configurables per tal de poder donar resposta a la majoria de necessitats que tinguin els usuaris de la PCCD.</p>

<p>Així, tant es pot cercar en el camp paremiotipus (per defecte), com en els camps de variants, sinònims i equivalents idiomàtics (marcant les caselles corresponents sota el quadre principal de cerca).</p>

<p>Per facilitar les cerques, no es tenen en compte les majúscules ni els signes d'accentuació. La funcionalitat de cerca permet trobar frases per mots i per fragments en diferents modes:</p>

<h3>Mode de cerca <em>conté</em></h3>

<p>Aquest és el mode de cerca per defecte. Si hom busca diversos termes (p. ex. <span class="text-monospace text-nowrap">gener febrer</span>), apareixeran resultats que inclouen com a mínim aquests termes. L'ordre i la freqüència dels termes no altera els resultats.</p>

<p>Si voleu recuperar una frase sencera, poseu-la entre cometes <span class="text-monospace text-nowrap">"…"</span>.</p>

<p>L'operador de resta <span class="text-monospace">-</span> indica que el terme no ha de ser present. Per exemple, la cerca <span class="text-monospace text-nowrap">pluja -boira</span> retornarà resultats que inclouen <em>pluja</em> però no <em>boira</em>.</p>

<p>En cercar un sol terme, els caràcters <span class="text-monospace">*</span> i <span class="text-monospace">?</span> funcionen com a comodins, i serveixen per cercar frases que contenen fragments d'un mot. Per exemple, la cerca <span class="text-monospace text-nowrap">*pobre*</span> retornarà resultats amb els termes <em>afartapobres</em>, <em>pobre</em>, <em>pobres</em> i <em>pobresa</em>, mentre que la cerca <span class="text-monospace text-nowrap">ll?bre</span> retornarà resultats amb els termes <em>llebre</em> i <em>llibre</em>.</p>

<h3>Mode de cerca <em>comença per</em></h3>

<p>Permet cercar frases que comencen pel fragment indicat.</p>

<h3>Mode de cerca <em>acaba en</em></h3>

<p>Permet cercar frases que acaben en el fragment indicat.</p>

<h3>Mode de cerca <em>coincident</em></h3>

<p>Permet cercar frases que coincideixen exactament amb el fragment indicat.</p>
