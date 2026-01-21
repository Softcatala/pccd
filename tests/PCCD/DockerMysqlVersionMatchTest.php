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
final class DockerMysqlVersionMatchTest extends TestCase
{
    public function testDockerMysqlVersionMatch(): void
    {
        $dockerFile = file_get_contents(__DIR__ . '/../../.docker/sql.Dockerfile');
        $dockerVersionProd = $this->getDockerMysqlVersion($dockerFile);

        $composeFiles = [
            'docker-compose.yml',
            'docker-compose.fpm.yml',
            'docker-compose.frankenphp.yml',
        ];

        foreach ($composeFiles as $composeFile) {
            $dockerComposeFile = file_get_contents(__DIR__ . '/../../' . $composeFile);
            $dockerVersionDev = $this->getDockerComposeMysqlVersion($dockerComposeFile);

            self::assertSame($dockerVersionDev, $dockerVersionProd, "{$composeFile} and sql.Dockerfile should use the same MariaDB version");
        }
    }

    private function getDockerMysqlVersion(string $dockerFile): string
    {
        preg_match('/^FROM mariadb:([0-9.]+)/', $dockerFile, $matches);

        return $matches[1];
    }

    private function getDockerComposeMysqlVersion(string $dockerComposeFile): string
    {
        preg_match('/image: mariadb:([0-9.]+)/', $dockerComposeFile, $matches);

        return $matches[1];
    }
}
