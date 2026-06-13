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

// Code to prepend when using XHProf in dev environments.
xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
register_shutdown_function(
    static function (): void {
        file_put_contents('/tmp/' . uniqid() . '.PCCD.xhprof', serialize(xhprof_disable()));
    }
);
