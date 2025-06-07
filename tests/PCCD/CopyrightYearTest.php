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

final class CopyrightYearTest extends TestCase
{
    public function testCreditsPageHasCorrectCopyrightYear(): void
    {
        $year = date('Y');
        $phpFile = file_get_contents(__DIR__ . '/../../src/pages/credits.php');
        $yearMentions = substr_count($phpFile, "© Víctor Pàmies i Riudor, 2020-{$year}.");

        $this->assertSame(2, $yearMentions, "File src/pages/credits.php should contain the current year {$year} twice");
    }

    public function testTemplatePageHasCorrectCopyrightYear(): void
    {
        $year = date('Y');
        $phpFile = file_get_contents(__DIR__ . '/../../src/templates/main.php');
        $yearMentions = substr_count($phpFile, "© Víctor Pàmies i Riudor, 2020-{$year}.");

        $this->assertSame(1, $yearMentions, "File src/templates/main.php should contain the current year {$year}");
    }
}
