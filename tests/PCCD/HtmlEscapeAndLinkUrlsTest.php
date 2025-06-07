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

final class HtmlEscapeAndLinkUrlsTest extends TestCase
{
    public function testConvertsUrlsToLinks(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        $text = 'Check out https://www.example.com for more information.';
        $result = html_escape_and_link_urls($text);

        $expected = 'Check out <a class="external" target="_blank" rel="noopener" href="https://www.example.com">https://www.example.com</a> for more information.';
        $this->assertSame($expected, $result);
    }

    public function testEscapesHtmlEntities(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        $text = '<b>This is bold text</b> and this is a URL: https://www.example.com';
        $result = html_escape_and_link_urls($text);

        $expected = '&lt;b&gt;This is bold text&lt;/b&gt; and this is a URL: <a class="external" target="_blank" rel="noopener" href="https://www.example.com">https://www.example.com</a>';
        $this->assertSame($expected, $result);
    }

    public function testNonUrlsAreNotConverted(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        $text = 'This is just plain text without any links.';
        $result = html_escape_and_link_urls($text);

        $expected = 'This is just plain text without any links.';
        $this->assertSame($expected, $result);
    }

    public function testUrlWithoutScheme(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        $text = 'Visit www.example.com for more details.';
        $result = html_escape_and_link_urls($text);

        $expected = 'Visit www.example.com for more details.';
        $this->assertSame($expected, $result);
    }

    public function testSingleHttpUrl(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        $text = 'http://www.exemple.cat/';
        $result = html_escape_and_link_urls($text);

        $expected = '<a class="external" target="_blank" rel="noopener" href="http://www.exemple.cat/">http://www.exemple.cat/</a>';
        $this->assertSame($expected, $result);
    }

    public function testTildeInUrl(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        $text = 'https://usuaris.tinet.cat/~netol/';
        $result = html_escape_and_link_urls($text);

        $expected = '<a class="external" target="_blank" rel="noopener" href="https://usuaris.tinet.cat/~netol/">https://usuaris.tinet.cat/~netol/</a>';
        $this->assertSame($expected, $result);
    }

    public function testEndingExclamationMark(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        $text = 'Check https://example.com! Now';
        $result = html_escape_and_link_urls($text);

        $expected = 'Check <a class="external" target="_blank" rel="noopener" href="https://example.com">https://example.com</a>! Now';
        $this->assertSame($expected, $result);
    }

    public function testEndingDot(): void
    {
        require_once __DIR__ . '/../../src/common.php';

        $text = 'Check https://example.com. Ok';
        $result = html_escape_and_link_urls($text);

        $expected = 'Check <a class="external" target="_blank" rel="noopener" href="https://example.com">https://example.com</a>. Ok';
        $this->assertSame($expected, $result);
    }
}
