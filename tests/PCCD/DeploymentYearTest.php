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

namespace PCCD;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class DeploymentYearTest extends TestCase
{
    public function testPageHasCorrectDeploymentDateYear(): void
    {
        $date = file_get_contents(__DIR__ . '/../../tmp/date.txt');
        $year = date('Y');
        $matches = [];
        preg_match('/^.*([0-9]{4}).*$/', $date, $matches);

        static::assertSame($year, $matches[1], "File tmp/date.txt should contain the current year {$year}");
    }
}
