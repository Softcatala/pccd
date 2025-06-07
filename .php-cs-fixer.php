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
    ->exclude(['node_modules', 'src/third_party', 'tmp'])
    ->ignoreDotFiles(false)
    ->in(__DIR__);

$config = new Config();
$config
    ->setRiskyAllowed(true)
    ->setRules([
        // Import some generally useful rule sets.
        '@PHP82Migration:risky' => true,
        '@PHP84Migration' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PHPUnit100Migration:risky' => true,
        // Override some insanse rules of these sets.
        'concat_space' => ['spacing' => 'one'],
        // Having strict types is not worth it when using phpstan/psalm.
        'declare_strict_types' => false,
        'header_comment' => ['header' => $header, 'comment_type' => 'PHPDoc', 'location' => 'after_open'],
        'increment_style' => ['style' => 'post'],
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'php_unit_internal_class' => false,
        'php_unit_test_case_static_method_calls' => ['call_type' => 'this'],
        'php_unit_test_class_requires_covers' => false,
        'phpdoc_align' => false,
        'phpdoc_annotation_without_dot' => false,
        'phpdoc_to_comment' => false,
        'psr_autoloading' => false,
        // Cryptographically secure functions are not required.
        'random_api_migration' => false,
        'yoda_style' => false,
    ])
    ->setFinder($finder);

return $config;
