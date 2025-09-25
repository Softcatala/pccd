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
final class DockerPhpVersionMatchTest extends TestCase
{
    public function testPhpVersionMatch(): void
    {
        $alpineDevDockerfile = file_get_contents(__DIR__ . '/../../.docker/alpine.dev.Dockerfile');
        $alpineEdgeDockerfile = file_get_contents(__DIR__ . '/../../.docker/alpine.edge.Dockerfile');
        $webAlpineProdDockerfile = file_get_contents(__DIR__ . '/../../.docker/web-alpine.prod.Dockerfile');
        $debianDevDockerfile = file_get_contents(__DIR__ . '/../../.docker/debian.dev.Dockerfile');

        $alpineDevPhpVersion = $this->getPhpVersionFromAlpineDockerfile($alpineDevDockerfile);
        $alpineEdgePhpVersion = $this->getPhpVersionFromAlpineDockerfile($alpineEdgeDockerfile);
        $webAlpineProdPhpVersion = $this->getPhpVersionFromAlpineDockerfile($webAlpineProdDockerfile);
        $debianDevPhpMajorVersion = $this->getPhpMajorVersionFromDebianDockerfile($debianDevDockerfile);

        $this->assertSame($debianDevPhpMajorVersion, $alpineDevPhpVersion, 'Alpine dev PHP version should match Debian dev PHP major version');
        $this->assertGreaterThanOrEqual($debianDevPhpMajorVersion, $alpineEdgePhpVersion, 'Alpine edge PHP version should be greater than or equal to Debian dev PHP major version');
        $this->assertSame($debianDevPhpMajorVersion, $webAlpineProdPhpVersion, 'Web Alpine prod PHP version should match Debian dev PHP major version');
    }

    private function getPhpVersionFromAlpineDockerfile(string $dockerfileContent): string
    {
        preg_match('/^ARG PHP_VERSION=([0-9]+)/m', $dockerfileContent, $matches);

        return $matches[1];
    }

    private function getPhpMajorVersionFromDebianDockerfile(string $dockerfileContent): string
    {
        preg_match('/^ARG PHP_IMAGE_TAG=([0-9]+\.[0-9]+)/m', $dockerfileContent, $matches);

        return str_replace('.', '', $matches[1]);
    }
}
