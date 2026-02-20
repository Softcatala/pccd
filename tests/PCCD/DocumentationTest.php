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
    public function testReadmePhpBadgeUrl(): void
    {
        $phpVersion = $this->getPhpVersionFromComposer();
        $expectedBadgeUrl = "https://img.shields.io/badge/PHP-{$phpVersion}+-777BB4?logo=php";

        self::assertStringContainsString($expectedBadgeUrl, $this->getReadme(), "README should contain PHP badge URL '{$expectedBadgeUrl}'");
    }

    public function testReadmePhpBadgeLabel(): void
    {
        $phpVersion = $this->getPhpVersionFromComposer();

        self::assertStringContainsString("[![PHP {$phpVersion}+]", $this->getReadme(), "README should contain PHP badge label 'PHP {$phpVersion}+'");
    }

    public function testReadmePhpRequirement(): void
    {
        $phpVersion = $this->getPhpVersionFromComposer();

        self::assertStringContainsString("- PHP {$phpVersion}+", $this->getReadme(), "README requirements list should contain 'PHP {$phpVersion}+'");
    }

    public function testReadmeNodeBadgeUrl(): void
    {
        $nodeVersion = $this->getNodeVersionFromPackage();
        $expectedBadgeUrl = "https://img.shields.io/badge/Node.js-{$nodeVersion}+-339933?logo=node.js";

        self::assertStringContainsString($expectedBadgeUrl, $this->getReadme(), "README should contain Node.js badge URL '{$expectedBadgeUrl}'");
    }

    public function testReadmeNodeBadgeLabel(): void
    {
        $nodeVersion = $this->getNodeVersionFromPackage();

        self::assertStringContainsString("[![Node.js {$nodeVersion}+]", $this->getReadme(), "README should contain Node.js badge label 'Node.js {$nodeVersion}+'");
    }

    public function testReadmeNodeRequirement(): void
    {
        $nodeVersion = $this->getNodeVersionFromPackage();

        self::assertStringContainsString("- Node.js {$nodeVersion}+", $this->getReadme(), "README requirements list should contain 'Node.js {$nodeVersion}+'");
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
