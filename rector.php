<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/Tests',
    ])
    ->withSets([
        Typo3LevelSetList::UP_TO_TYPO3_11,
    ])
    ->withPhpSets(php81: true)
    ->withRules([
        //AddVoidReturnTypeWhereNoReturnRector::class,
    ]);
