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

function test_modismes_repetits(): void
{
    echo '<h3>Modismes amb diferències de caràcters que es poden confondre visualment (consecutius)</h3>';
    $output = trim((string) @file_get_contents(__DIR__ . '/../../data/reports/test_intl_modismes_repetits.txt'));
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}
