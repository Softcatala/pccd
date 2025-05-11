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

use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Rector\PHPUnit\Set\PHPUnitSetList;

return RectorConfig::configure()
    ->withPaths([
        __FILE__,
        __DIR__ . '/docroot',
        __DIR__ . '/scripts',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRootFiles()
    ->withSkip([
        __DIR__ . '/src/third_party/*',
        EncapsedStringsToSprintfRector::class,
        NewlineAfterStatementRector::class,
        RenameForeachValueVariableToMatchExprVariableRector::class,
        RenameParamToMatchTypeRector::class,
    ])
    ->withSets([
        PHPUnitSetList::PHPUNIT_110,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ])
    ->withPreparedSets(
        codeQuality: true,
        codingStyle: true,
        deadCode: true,
        earlyReturn: true,
        naming: true,
        typeDeclarations: true
    )
    ->withConfiguredRule(SimplifyUselessVariableRector::class, [
        SimplifyUselessVariableRector::ONLY_DIRECT_ASSIGN => true,
    ])
    ->withPhpSets();
