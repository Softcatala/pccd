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
final class CiNpmScriptsTest extends TestCase
{
    public function testGitlabAndGithubRunTheSameNpmScripts(): void
    {
        $gitlabScripts = $this->getNpmScripts(__DIR__ . '/../../.gitlab-ci.yml');
        $githubScripts = $this->getNpmScripts(__DIR__ . '/../../.github/workflows/docker-test.yml');

        self::assertNotEmpty($gitlabScripts, 'Could not find any npm scripts in .gitlab-ci.yml');
        self::assertSame(
            $gitlabScripts,
            $githubScripts,
            'GitLab CI and GitHub Actions must run the same npm scripts'
        );
    }

    private function getNpmScripts(string $filename): array
    {
        $content = file_get_contents($filename);
        self::assertIsString($content, "Could not read {$filename}");

        preg_match_all('/npm run (lint:[a-z0-9-]+|test:node)\b/', $content, $matches);
        $scripts = array_values(array_unique($matches[1]));
        sort($scripts);

        return $scripts;
    }
}
