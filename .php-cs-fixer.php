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

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$header = <<<'EOF'
    This file is part of PCCD.

    (c) Pere Orga Esteve <pere@orga.cat>
    (c) Víctor Pàmies i Riudor <vpamies@gmail.com>

    This source file is subject to the AGPL license that is bundled with this
    source code in the file LICENSE.
    EOF;

$finder = Finder::create()
    ->exclude(['src/third_party'])
    // @TODO v4 line no longer needed.
    ->ignoreDotFiles(false)
    // @TODO v4 line no longer needed.
    ->ignoreVCSIgnored(true)
    ->in(__DIR__);

$config = new Config();
$config
    ->setUnsupportedPhpVersionAllowed(true)
    ->setRiskyAllowed(true)
    ->setRules([
        // Import some generally useful rule sets.
        '@auto' => true,
        '@auto:risky' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        // Override some insanse rules of these sets.
        'concat_space' => ['spacing' => 'one'],
        // Having strict types is not worth it when using phpstan/psalm.
        'declare_strict_types' => false,
        // Standarize file headers.
        'header_comment' => ['header' => $header, 'comment_type' => 'PHPDoc', 'location' => 'after_open'],
        'increment_style' => ['style' => 'post'],
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        // Conflicts with php-cs settings.
        'phpdoc_annotation_without_dot' => false,
        'phpdoc_to_comment' => false,
        // PSR-4 is not followed.
        'psr_autoloading' => false,
        // Cryptographically secure functions are not required.
        'random_api_migration' => false,
        'yoda_style' => false,
    ])
    ->setFinder($finder);

return $config;
