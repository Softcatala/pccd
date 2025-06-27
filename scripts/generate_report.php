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

/*
 * Execute time-consuming tests or that require additional packages, such as intl and curl PHP extensions.
 *
 * This file is called by npm generate:reports script.
 */

require __DIR__ . '/../src/reports/offline.php';

ini_set('memory_limit', '1024M');

if (!isset($argv[1])) {
    echo 'Argument is required.' . "\n";

    exit(1);
}

$function_name = 'background_test_' . $argv[1];
if (function_exists($function_name)) {
    $start = (int) ($argv[2] ?? 0);
    $end = (int) ($argv[3] ?? 0);
    if ($start !== 0 || $end !== 0) {
        echo $function_name($start, $end);
    } else {
        echo $function_name();
    }

    exit;
}

echo 'Unknown test name provided.' . "\n";

exit(1);
