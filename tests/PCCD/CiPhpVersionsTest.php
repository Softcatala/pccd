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
final class CiPhpVersionsTest extends TestCase
{
    public function testDockerPhpProductionVersionsMatchGitlabCi(): void
    {
        $gitlabCiContent = file_get_contents(__DIR__ . '/../../.gitlab-ci.yml');
        self::assertIsString($gitlabCiContent);

        preg_match_all('/PHP_VERSION: "(.*?)"/', $gitlabCiContent, $matches);
        self::assertNotEmpty($matches[1], 'Could not find any PHP_VERSION in .gitlab-ci.yml matrix');
        $gitlabCiPhpVersions = array_map(static fn (string $version): string => str_replace('.', '', $version), $matches[1]);

        $dockerfiles = [
            __DIR__ . '/../../.docker/fpm.Dockerfile',
            __DIR__ . '/../../.docker/dev.Dockerfile',
        ];

        foreach ($dockerfiles as $dockerfile) {
            $dockerfileContent = file_get_contents($dockerfile);
            self::assertIsString($dockerfileContent);

            $dockerfilePhpVersion = '';
            if (preg_match('/ARG PHP_VERSION=(\d+)/', $dockerfileContent, $matches)) {
                $dockerfilePhpVersion = $matches[1];
            } elseif (preg_match('/ARG PHP_IMAGE_TAG=(\d+\.\d+).*?/', $dockerfileContent, $matches)) {
                $dockerfilePhpVersion = str_replace('.', '', $matches[1]);
            }

            self::assertNotEmpty($dockerfilePhpVersion, 'Could not find PHP version in ' . basename($dockerfile));
            self::assertContains(
                $dockerfilePhpVersion,
                $gitlabCiPhpVersions,
                'PHP version in ' . basename($dockerfile) . " ({$dockerfilePhpVersion}) is not present in .gitlab-ci.yml matrix"
            );
        }
    }
}
