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

declare(strict_types=1);

// PHP Coding Standards Fixer (PHP CS Fixer) settings.

$header = <<<'EOF'
    This file is part of PCCD.

    (c) Pere Orga Esteve <pere@orga.cat>
    (c) Víctor Pàmies i Riudor <vpamies@gmail.com>

    This source file is subject to the AGPL license that is bundled with this
    source code in the file LICENSE.
    EOF;

$finder = PhpCsFixer\Finder::create()
    ->notPath('vendor')
    ->notPath('node_modules')
    ->notPath('src/third_party')
    ->name('*.php')
    ->ignoreDotFiles(false)
    ->in(__DIR__);

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP80Migration:risky' => true,
        '@PHP82Migration' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PHPUnit100Migration:risky' => true,
        'concat_space' => ['spacing' => 'one'],
        'header_comment' => ['header' => $header, 'comment_type' => 'PHPDoc', 'location' => 'after_open'],
        'increment_style' => ['style' => 'post'],
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        // 'no_blank_lines_after_phpdoc' => false,
        'phpdoc_to_comment' => false,
        'random_api_migration' => true,
        'yoda_style' => false,
    ])
    ->setFinder($finder);

return $config;
