<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\CodeQuality\General\ExtEmConfRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/Tests',
    ])
    ->withPhpVersion(PhpVersion::PHP_72)
    //->withPhpSets(PhpVersion::PHP_72)
    ->withSets([
        Typo3LevelSetList::UP_TO_TYPO3_11,
    ])
    ->withPHPStanConfigs([
        Typo3Option::PHPSTAN_FOR_RECTOR_PATH
    ])
    ->withConfiguredRule(ExtEmConfRector::class, [
        ExtEmConfRector::PHP_VERSION_CONSTRAINT => '7.2.0-7.4.99',
        ExtEmConfRector::TYPO3_VERSION_CONSTRAINT => '10.4.0-11.5.99',
        ExtEmConfRector::ADDITIONAL_VALUES_TO_BE_REMOVED => []
    ])
    ->withRules([
        //AddVoidReturnTypeWhereNoReturnRector::class,
    ]);
