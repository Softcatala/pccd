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

function test_ortografia(): void
{
    require_once __DIR__ . '/../common.php';

    $paremiotipus = db_query('SELECT DISTINCT `PAREMIOTIPUS` FROM `00_PAREMIOTIPUS` ORDER BY `PAREMIOTIPUS`')->fetchAll(PDO::FETCH_COLUMN);
    $show_check = static function (string $title, callable $matches) use ($paremiotipus): void {
        echo "<h3>{$title}</h3>";
        $output = '';
        foreach ($paremiotipus as $paremiotipus_value) {
            assert(is_string($paremiotipus_value));
            if ($matches($paremiotipus_value)) {
                $output .= get_paremiotipus_display($paremiotipus_value, escape_html: false) . "\n";
            }
        }
        if ($output === '') {
            echo '<pre class="empty">(cap resultat)</pre>';
        } else {
            echo "<pre>{$output}</pre>";
        }
    };

    $show_check(
        'Paremiotipus amb una ela geminada o un punt volat mal escrit',
        static fn (string $value): bool => preg_match('/[lL]\.[lL]|[^lL]·|·[^lL]/u', $value) === 1
    );

    $show_check(
        'Paremiotipus amb errors ortogràfics coneguts',
        static function (string $value): bool {
            $patterns = [
                '/\bPais\s+Valenci/iu',
                '/\bPallars\s+Sovir/iu',
                '/\bfixe\b/iu',
                '/\bal darrera\b/iu',
                '/\bendarrera\b/iu',
                '/\btraïr\b/iu',
                '/\bnèixer\b/iu',
                '/\bla última\b/iu',
            ];
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $value) === 1) {
                    return true;
                }
            }

            $display = get_paremiotipus_display($value, escape_html: false);
            $unquoted = preg_replace('/«[^»]*»|“[^”]*”|"[^"]*"/u', '', $display) ?? $display;

            return preg_match('/\bpero\b/iu', $unquoted) === 1;
        }
    );

    $show_check(
        'Paremiotipus amb la còpula «son» sense accent',
        static fn (string $value): bool => preg_match('/\bson\s+(?:el|els|la|les|un|uns|una|unes|molt|molts|molta|moltes|poc|pocs|poca|poques|capgrossos|com)\b/iu', $value) === 1
    );
}
