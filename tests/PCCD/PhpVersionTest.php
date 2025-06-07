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

final class PhpVersionTest extends TestCase
{
    public function testShellNixHasAtLeastComposerJsonPhpVersion(): void
    {
        $composerJsonContent = file_get_contents(__DIR__ . '/../../composer.json');
        $composerJson = json_decode(json: $composerJsonContent, associative: true, flags: JSON_THROW_ON_ERROR);
        \assert(\is_string($composerJson['require']['php']));
        $composerPhpVersion = trim($composerJson['require']['php'], '>=^');

        $shellNixPath = realpath(__DIR__ . '/../../shell.nix');
        $shellNixContent = file_get_contents($shellNixPath);

        // Assuming the PHP version in shell.nix is defined in a line like php82.withExtensions.
        preg_match('/php(\d+)\.withExtensions/', $shellNixContent, $matches);
        $nixPhpVersion = $matches[1][0] . '.' . $matches[1][1];

        $this->assertTrue(version_compare($nixPhpVersion, $composerPhpVersion, '>='), "PHP version in shell.nix ({$nixPhpVersion}) is lower than composer.json minimum version ({$composerPhpVersion})");
    }
}
