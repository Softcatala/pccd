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
    public function testDockerPhpVersionMatch(): void
    {
        $dockerFileDev = file_get_contents(__DIR__ . '/../../.docker/debian.dev.Dockerfile');
        $dockerFileProd = file_get_contents(__DIR__ . '/../../.docker/web-debian.prod.Dockerfile');

        $dockerVersionDev = $this->getDockerPhpTagVersion($dockerFileDev);
        $dockerVersionProd = $this->getDockerPhpVersion($dockerFileProd);

        self::assertSame($dockerVersionDev, $dockerVersionProd, 'Debian dev and prod Docker files should use the same PHP version');
    }

    public function testDockerMysqlVersionMatch(): void
    {
        $dockerComposeFile = file_get_contents(__DIR__ . '/../../docker-compose.yml');
        $dockerFile = file_get_contents(__DIR__ . '/../../.docker/sql.prod.Dockerfile');

        $dockerVersionDev = $this->getDockerComposeMysqlVersion($dockerComposeFile);
        $dockerVersionProd = $this->getDockerMysqlVersion($dockerFile);

        self::assertSame($dockerVersionDev, $dockerVersionProd, 'docker-compose.yml and sql.prod.Dockerfile should use the same MariaDB version');
    }

    public function testAlpineDockerPhpVersionMatch(): void
    {
        $alpineFileDev = file_get_contents(__DIR__ . '/../../.docker/alpine.dev.Dockerfile');
        $alpineFileProd = file_get_contents(__DIR__ . '/../../.docker/web-alpine.prod.Dockerfile');
        $debianFile = file_get_contents(__DIR__ . '/../../.docker/web-debian.prod.Dockerfile');

        $alpineVersionDev = $this->getAlpineDockerPhpVersion($alpineFileDev);
        $alpineVersionProd = $this->getAlpineDockerPhpVersion($alpineFileProd);
        $debianVersion = $this->getDockerPhpVersion($debianFile, patch: false);

        self::assertSame($alpineVersionDev, $alpineVersionProd, 'Alpine dev and prod Docker files should use the same PHP version');
        self::assertSame($alpineVersionDev, $debianVersion, 'Alpine and Debian Docker files should use the same PHP version');
    }

    protected function getAlpineDockerPhpVersion(string $dockerFile): string
    {
        preg_match('/php(\d{2})-apache2/', $dockerFile, $matches);

        // Convert '83' to '8.3' for example.
        return substr($matches[1], 0, 1) . '.' . substr($matches[1], 1);
    }

    protected function getDockerPhpTagVersion(string $dockerFile, bool $patch = true): string
    {
        if ($patch) {
            preg_match('/^ARG PHP_IMAGE_TAG=([0-9.]+(-rc|beta\d+)?)-apache/', $dockerFile, $matches);

            return $matches[1];
        }

        preg_match('/^ARG PHP_IMAGE_TAG=(\d+)\.(\d+)(\.\d+)?(-rc|beta\d+)?-apache/', $dockerFile, $matches);

        // Concatenate major and minor version parts.
        return $matches[1] . '.' . $matches[2];
    }

    protected function getDockerPhpVersion(string $dockerFile, bool $patch = true): string
    {
        if ($patch) {
            preg_match('/^FROM php:([0-9.]+(-rc|beta\d+)?)-apache/', $dockerFile, $matches);

            return $matches[1];
        }

        preg_match('/^FROM php:(\d+)\.(\d+)(\.\d+)?(-rc|beta\d+)?-apache/', $dockerFile, $matches);

        // Concatenate major and minor version parts.
        return $matches[1] . '.' . $matches[2];
    }

    protected function getDockerMysqlVersion(string $dockerFile): string
    {
        preg_match('/^FROM mariadb:([0-9.]+)/', $dockerFile, $matches);

        return $matches[1];
    }

    protected function getDockerComposeMysqlVersion(string $dockerComposeFile): string
    {
        preg_match('/image: mariadb:([0-9.]+)/', $dockerComposeFile, $matches);

        return $matches[1];
    }
}
