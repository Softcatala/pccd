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

final class DockerVersionTest extends TestCase
{
    public function testDockerMysqlVersionMatch(): void
    {
        $dockerComposeFile = file_get_contents(__DIR__ . '/../../docker-compose.yml');
        $dockerFile = file_get_contents(__DIR__ . '/../../.docker/sql.prod.Dockerfile');

        $dockerVersionDev = $this->getDockerComposeMysqlVersion($dockerComposeFile);
        $dockerVersionProd = $this->getDockerMysqlVersion($dockerFile);

        $this->assertSame($dockerVersionDev, $dockerVersionProd, 'docker-compose.yml and sql.prod.Dockerfile should use the same MariaDB version');
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
