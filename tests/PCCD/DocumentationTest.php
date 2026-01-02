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

/**
 * @internal
 *
 * @coversNothing
 */
final class DocumentationTest extends TestCase
{
    public function testReadmeHasCorrectPhpMinimumVersion(): void
    {
        $composerPhpVersion = $this->getPhpVersionFromComposer();
        $minimumVersionInformation = "PHP: version {$composerPhpVersion} or later is required.";

        $this->assertStringContainsString($composerPhpVersion, $this->getReadme(), "Readme needs to contain information '{$minimumVersionInformation}'");
    }

    public function testReadmeHasCorrectNodeJsMinimumVersion(): void
    {
        $minimumVersion = $this->getNodeVersionFromPackage();
        $minimumVersionInformation = "Node.js: version {$minimumVersion} or later is required.";

        $this->assertStringContainsString($minimumVersionInformation, $this->getReadme(), "Readme needs to contain information '{$minimumVersionInformation}'");
    }

    public function testReadmePhpBadgeHasCorrectVersion(): void
    {
        $composerPhpVersion = $this->getPhpVersionFromComposer();
        $expectedBadgeUrl = "https://img.shields.io/badge/PHP-{$composerPhpVersion}+-777BB4?logo=php";

        $this->assertStringContainsString($expectedBadgeUrl, $this->getReadme(), "Readme needs to contain PHP badge URL with version '{$composerPhpVersion}+'");
    }

    public function testReadmePhpBadgeHasCorrectLabel(): void
    {
        $composerPhpVersion = $this->getPhpVersionFromComposer();
        $expectedLabel = "![PHP {$composerPhpVersion}+]";

        $this->assertStringContainsString($expectedLabel, $this->getReadme(), "Readme needs to contain PHP badge label 'PHP {$composerPhpVersion}+'");
    }

    public function testReadmeNodeBadgeHasCorrectVersion(): void
    {
        $nodeVersion = $this->getNodeVersionFromPackage();
        $expectedBadgeUrl = "https://img.shields.io/badge/Node.js-{$nodeVersion}+-339933?logo=node.js";

        $this->assertStringContainsString($expectedBadgeUrl, $this->getReadme(), "Readme needs to contain Node.js badge URL with version '{$nodeVersion}+'");
    }

    public function testReadmeNodeBadgeHasCorrectLabel(): void
    {
        $nodeVersion = $this->getNodeVersionFromPackage();
        $expectedLabel = "![Node.js {$nodeVersion}+]";

        $this->assertStringContainsString($expectedLabel, $this->getReadme(), "Readme needs to contain Node.js badge label 'Node.js {$nodeVersion}+'");
    }

    private function getPhpVersionFromComposer(): string
    {
        $composerJsonContent = file_get_contents(__DIR__ . '/../../composer.json');
        $composerJson = json_decode(json: $composerJsonContent, associative: true, flags: JSON_THROW_ON_ERROR);
        \assert(\is_string($composerJson['require']['php']));

        return trim($composerJson['require']['php'], '>=^');
    }

    private function getNodeVersionFromPackage(): string
    {
        $packageJsonContent = file_get_contents(__DIR__ . '/../../package.json');
        $packageJson = json_decode(json: $packageJsonContent, associative: true, flags: JSON_THROW_ON_ERROR);
        \assert(\is_string($packageJson['engines']['node']));

        return trim($packageJson['engines']['node'], '>=^');
    }

    private function getReadme(): string
    {
        $readmePath = realpath(__DIR__ . '/../../README.md');
        \assert(\is_string($readmePath));
        $readme = file_get_contents($readmePath);
        \assert(\is_string($readme));

        return $readme;
    }
}
