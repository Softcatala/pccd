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

namespace PCCD;

use PHPUnit\Framework\TestCase;

final class DeploymentYearTest extends TestCase
{
    public function testPageHasCorrectDeploymentDateYear(): void
    {
        $date = file_get_contents(__DIR__ . '/../../tmp/db_date.txt');
        $currentYear = date('Y');
        $previousYear = (string) ((int) $currentYear - 1);
        preg_match('/^.*(\d{4}).*$/', $date, $matches);
        $fileYear = $matches[1] ?? null;

        $acceptableYears = [$currentYear];
        if (date('n') === '1') {
            // January is month 1.
            $acceptableYears[] = $previousYear;
        }

        $this->assertContains(
            $fileYear,
            $acceptableYears,
            "File tmp/db_date.txt should contain the current year {$currentYear}"
            . (date('n') === '1' ? " or the previous year {$previousYear} in January" : '')
        );
    }
}
