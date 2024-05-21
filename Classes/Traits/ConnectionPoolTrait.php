<?php

namespace Localizationteam\Localizer\Traits;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait ConnectionPoolTrait
{
    public static function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }

    public static function getConnectionForTable(string $table): Connection
    {
        return self::getConnectionPool()->getConnectionForTable($table);
    }

    public static function getQueryBuilderForTable(string $table): QueryBuilder
    {
        return self::getConnectionPool()->getQueryBuilderForTable($table);
    }
}
