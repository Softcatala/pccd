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

final class ComposerPackageJsonTest extends TestCase
{
    private array $package;
    private array $composer;

    protected function setUp(): void
    {
        $this->package = $this->getJsonArray(__DIR__ . '/../../package.json');
        $this->composer = $this->getJsonArray(__DIR__ . '/../../composer.json');
    }

    public function testComposerPackageMatch(): void
    {
        $fields = [
            'keywords',
            'description',
            'homepage',
            'license',
        ];

        foreach ($fields as $field) {
            $this->assertSame($this->package[$field], $this->composer[$field], "Field {$field} in package.json and composer.json must match");
        }
    }

    private function getJsonArray(string $filename): array
    {
        return json_decode(json: file_get_contents($filename), associative: true, flags: JSON_THROW_ON_ERROR);
    }
}
