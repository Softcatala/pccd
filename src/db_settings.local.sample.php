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

declare(strict_types=1);

// db_settings.local.php example file.

putenv('MYSQL_DATABASE=pccd');
putenv('MYSQL_USER=pccd');
putenv('MYSQL_PASSWORD=yoursecretpassword');
putenv('MYSQL_HOSTNAME=localhost');
