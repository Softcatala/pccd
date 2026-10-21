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

    $paremiotipus = db_query('SELECT `Display` FROM `paremiotipus_display` ORDER BY `Display`')->fetchAll(PDO::FETCH_COLUMN);
    $show_check = static function (string $title, callable $matches) use ($paremiotipus): void {
        echo "<h3>{$title}</h3>";
        $output = '';
        foreach ($paremiotipus as $p) {
            if ($matches($p)) {
                $output .= $p . "\n";
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

            $unquoted = preg_replace('/«[^»]*»|“[^”]*”|"[^"]*"/u', '', $value);
            assert(is_string($unquoted));

            return preg_match('/\bpero\b/iu', $unquoted) === 1;
        }
    );

    $show_check(
        'Paremiotipus amb la còpula «son» sense accent',
        static fn (string $value): bool => preg_match('/\bson\s+(?:el|els|la|les|un|uns|una|unes|molt|molts|molta|moltes|poc|pocs|poca|poques|capgrossos|com)\b/iu', $value) === 1
    );

    echo '<h3>Paremiotipus amb problemes tipogràfics (punt volat, ela geminada)</h3>';
    $output = trim((string) @file_get_contents(__DIR__ . '/../../data/reports/test_intl_paremiotipus_tipografia.txt'));
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }

    echo '<h3>Modismes amb problemes tipogràfics (punt volat, ela geminada)</h3>';
    $output = trim((string) @file_get_contents(__DIR__ . '/../../data/reports/test_intl_modismes_tipografia.txt'));
    if ($output === '') {
        echo '<pre class="empty">(cap resultat)</pre>';
    } else {
        echo "<pre>{$output}</pre>";
    }
}
