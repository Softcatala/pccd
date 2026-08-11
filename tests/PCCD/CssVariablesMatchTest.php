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
final class CssVariablesMatchTest extends TestCase
{
    public function testColorPrimaryMatchesCss(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        $cssColorPrimary = $this->getCssCustomProperty('--color-primary');

        self::assertSame(
            $cssColorPrimary,
            COLOR_PRIMARY,
            'COLOR_PRIMARY in common.php must match --color-primary in variables.css'
        );
    }

    public function testBreakpointsMatchCss(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        $phpBreakpoints = $this->getPhpBreakpoints();
        $cssBreakpoints = $this->getCssBreakpoints();

        self::assertNotEmpty($phpBreakpoints, 'Expected at least one BREAKPOINT_* constant in common.php');
        self::assertNotEmpty($cssBreakpoints, 'Expected at least one --breakpoint-* in variables.css');

        foreach ($phpBreakpoints as $phpName => $phpValue) {
            $cssName = '--breakpoint-' . strtolower(substr($phpName, strlen('BREAKPOINT_')));

            self::assertArrayHasKey(
                $cssName,
                $cssBreakpoints,
                "{$phpName} in common.php has no matching {$cssName} in variables.css"
            );
            self::assertSame(
                $cssBreakpoints[$cssName],
                $phpValue,
                "{$phpName} in common.php must match {$cssName} in variables.css"
            );
        }
    }

    /** @return array<string, string> */
    private function getPhpBreakpoints(): array
    {
        $constants = get_defined_constants(true)['user'] ?? [];
        $breakpoints = [];

        foreach ($constants as $name => $value) {
            if (str_starts_with($name, 'BREAKPOINT_') && is_string($value)) {
                $breakpoints[$name] = $value;
            }
        }

        ksort($breakpoints);

        return $breakpoints;
    }

    /** @return array<string, string> */
    private function getCssBreakpoints(): array
    {
        $css = $this->getVariablesCss();
        preg_match_all('/@custom-media\s+(--breakpoint-[a-z0-9-]+)\s+(\([^)]+\))/', $css, $matches, PREG_SET_ORDER);

        $breakpoints = [];
        foreach ($matches as $match) {
            $breakpoints[$match[1]] = $match[2];
        }

        return $breakpoints;
    }

    private function getCssCustomProperty(string $name): string
    {
        $css = $this->getVariablesCss();
        preg_match('/' . preg_quote($name, '/') . ':\s*([^;]+);/', $css, $matches);
        self::assertNotEmpty($matches[1], "Could not extract {$name} from variables.css");

        return trim($matches[1]);
    }

    private function getVariablesCss(): string
    {
        $css = file_get_contents(__DIR__ . '/../../src/css/variables.css');
        self::assertIsString($css);

        return $css;
    }
}
