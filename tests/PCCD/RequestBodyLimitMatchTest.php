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
final class RequestBodyLimitMatchTest extends TestCase
{
    private const int EXPECTED_LIMIT_BYTES = 1024;

    public function testRequestBodyLimitsMatch(): void
    {
        $limits = [
            'Apache' => $this->getApacheLimit(),
            'FrankenPHP' => $this->getCaddyLimit(),
            'Nginx' => $this->getNginxLimit(),
            'PHP post_max_size' => $this->getPhpLimit(),
        ];

        foreach ($limits as $server => $limit) {
            self::assertSame(
                self::EXPECTED_LIMIT_BYTES,
                $limit,
                "{$server} request body limit should be " . self::EXPECTED_LIMIT_BYTES . ' bytes'
            );
        }
    }

    private function getApacheLimit(): int
    {
        $content = file_get_contents(__DIR__ . '/../../.docker/apache/vhost.conf');
        preg_match('/^\s*LimitRequestBody\s+(\S+)\s*$/m', $content, $matches);

        return $this->toBytes($matches[1] ?? '');
    }

    private function getNginxLimit(): int
    {
        $content = file_get_contents(__DIR__ . '/../../.docker/nginx/default.conf');
        preg_match('/^\s*client_max_body_size\s+(\S+);/m', $content, $matches);

        return $this->toBytes($matches[1] ?? '');
    }

    private function getCaddyLimit(): int
    {
        $content = file_get_contents(__DIR__ . '/../../.docker/frankenphp/Caddyfile');
        preg_match('/request_body\s*\{[^}]*\bmax_size\s+(\S+)/s', $content, $matches);

        return $this->toBytes($matches[1] ?? '');
    }

    private function getPhpLimit(): int
    {
        $content = file_get_contents(__DIR__ . '/../../.docker/php/custom.ini');
        preg_match('/^\s*post_max_size\s*=\s*(\S+)/m', $content, $matches);

        return $this->toBytes($matches[1] ?? '');
    }

    private function toBytes(string $value): int
    {
        return ini_parse_quantity(rtrim(trim($value), 'bB'));
    }
}
