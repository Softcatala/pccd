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
final class DockerStaticExtensionsMatchTest extends TestCase
{
    public function testStaticFileExtensionsMatch(): void
    {
        $apacheExtensions = $this->getApacheExtensions();
        $nginxExtensions = $this->getNginxExtensions();
        $caddyExtensions = $this->getCaddyExtensions();

        self::assertNotEmpty($apacheExtensions, 'Could not extract extensions from Apache vhost.conf');
        self::assertNotEmpty($nginxExtensions, 'Could not extract extensions from Nginx default.conf');
        self::assertNotEmpty($caddyExtensions, 'Could not extract extensions from FrankenPHP Caddyfile');

        self::assertSame($apacheExtensions, $nginxExtensions, 'Apache and Nginx static file extensions do not match');
        self::assertSame($apacheExtensions, $caddyExtensions, 'Apache and FrankenPHP static file extensions do not match');
    }

    /** @return list<string> */
    private function getApacheExtensions(): array
    {
        $content = file_get_contents(__DIR__ . '/../../.docker/apache/vhost.conf');
        preg_match('/FilesMatch.*?\.\(([^)]+)\)\$/', $content, $matches);

        $extensions = explode('|', $matches[1]);
        sort($extensions);

        return $extensions;
    }

    /** @return list<string> */
    private function getNginxExtensions(): array
    {
        $content = file_get_contents(__DIR__ . '/../../.docker/nginx/default.conf');
        preg_match('/location.*?\\\\\.\(\?:([^)]+)\)\$/', $content, $matches);

        $extensions = explode('|', $matches[1]);
        sort($extensions);

        return $extensions;
    }

    /** @return list<string> */
    private function getCaddyExtensions(): array
    {
        $content = file_get_contents(__DIR__ . '/../../.docker/frankenphp/Caddyfile');
        preg_match('/@static path (.+)/', $content, $matches);

        $extensions = array_map(
            static fn (string $ext): string => ltrim($ext, '*.'),
            explode(' ', $matches[1])
        );
        sort($extensions);

        return $extensions;
    }
}
