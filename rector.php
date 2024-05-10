<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\PostRector\Rector\NameImportingPostRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Ssch\TYPO3Rector\CodeQuality\General\ExtEmConfRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

return RectorConfig::configure()
    // Ensure file system caching is used instead of in-memory.
    ->withCache('var/cache/rector', FileCacheStorage::class)
    ->withPaths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/Tests',
    ])
    ->withPhpSets(php74: true)->withSets([
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
        Typo3LevelSetList::UP_TO_TYPO3_12,
    ])
    ->withImportNames()
    ->withPreparedSets(deadCode: true)
    ->withPHPStanConfigs([
        Typo3Option::PHPSTAN_FOR_RECTOR_PATH,
    ])->withConfiguredRule(ExtEmConfRector::class, [
        ExtEmConfRector::PHP_VERSION_CONSTRAINT => '8.1.00-8.2.99',
        ExtEmConfRector::TYPO3_VERSION_CONSTRAINT => '11.5.00-12.4.99',
        ExtEmConfRector::ADDITIONAL_VALUES_TO_BE_REMOVED => [],
    ])->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
        //ConvertImplicitVariablesToExplicitGlobalsRector::class,
    ])
    # If you use withImportNames(), you should consider excluding some TYPO3 files.
    ->withSkip([
        NameImportingPostRector::class => [
            'ext_localconf.php',
            // This line can be removed since TYPO3 11.4, see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.4/Important-94280-MoveContentsOfExtPhpIntoLocalScopes.html
            'ext_tables.php',
            // This line can be removed since TYPO3 11.4, see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.4/Important-94280-MoveContentsOfExtPhpIntoLocalScopes.html
            'ClassAliasMap.php',
        ],
    ]);
