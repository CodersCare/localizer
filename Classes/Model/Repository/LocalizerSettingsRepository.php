<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Model\Repository;

use Localizationteam\Localizer\Constants;
use PDO;
use TYPO3\CMS\Core\Database\Connection;

class LocalizerSettingsRepository extends AbstractRepository
{
    public function findByUid(int $uid, array $fields = ['*']): array
    {
        $connection = self::getConnectionForTable(Constants::TABLE_LOCALIZER_SETTINGS);
        $result = $connection->select($fields, Constants::TABLE_LOCALIZER_SETTINGS, ['uid' => $uid]);
        return $this->fetchAssociative($result);
    }

    public static function getConnectionForTable(string $table): Connection
    {
        return self::getConnectionPool()->getConnectionForTable($table);
    }

    public function loadAvailableLocalizers(): array
    {
        $localizers = $this->findAll();

        $availableLocalizers = [];
        foreach ($localizers as $localizer) {
            $availableLocalizers[$localizer['uid']] = $localizer;
        }

        return $availableLocalizers;
    }

    public function findAll(): array
    {
        $connection = self::getConnectionForTable(Constants::TABLE_LOCALIZER_SETTINGS);
        $result = $connection->select(['*'], Constants::TABLE_LOCALIZER_SETTINGS);
        return $this->fetchAllAssociative($result);
    }

    public function getLocalizerLanguages(int $localizerId): array
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_SETTINGS);
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->selectLiteral('MAX(sourceLanguage.uid) source, GROUP_CONCAT(targetLanguage.uid) target')
            ->from(Constants::TABLE_LOCALIZER_SETTINGS, 'settings')
            ->leftJoin(
                'settings',
                Constants::TABLE_LOCALIZER_LANGUAGE_MM,
                'sourceMM',
                (string)$queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'settings.uid',
                        $queryBuilder->quoteIdentifier('sourceMM.uid_local')
                    ),
                    $queryBuilder->expr()->eq(
                        'sourceMM.tablenames',
                        $queryBuilder->createNamedParameter(Constants::TABLE_STATIC_LANGUAGES, PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'sourceMM.ident',
                        $queryBuilder->createNamedParameter('source', PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'sourceMM.source',
                        $queryBuilder->createNamedParameter(Constants::TABLE_LOCALIZER_SETTINGS, PDO::PARAM_STR)
                    )
                )
            )
            ->leftJoin(
                'sourceMM',
                Constants::TABLE_STATIC_LANGUAGES,
                'sourceLanguage',
                (string)$queryBuilder->expr()->eq(
                    'sourceLanguage.uid',
                    $queryBuilder->quoteIdentifier('sourceMM.uid_foreign')
                )
            )
            ->leftJoin(
                'settings',
                Constants::TABLE_LOCALIZER_LANGUAGE_MM,
                'targetMM',
                (string)$queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'settings.uid',
                        $queryBuilder->quoteIdentifier('targetMM.uid_local')
                    ),
                    $queryBuilder->expr()->eq(
                        'targetMM.tablenames',
                        $queryBuilder->createNamedParameter(Constants::TABLE_STATIC_LANGUAGES, PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'targetMM.ident',
                        $queryBuilder->createNamedParameter('target', PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'targetMM.source',
                        $queryBuilder->createNamedParameter(Constants::TABLE_LOCALIZER_SETTINGS, PDO::PARAM_STR)
                    )
                )
            )
            ->leftJoin(
                'targetMM',
                Constants::TABLE_STATIC_LANGUAGES,
                'targetLanguage',
                (string)$queryBuilder->expr()->eq(
                    'targetLanguage.uid',
                    $queryBuilder->quoteIdentifier('targetMM.uid_foreign')
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'settings.uid',
                    $localizerId
                )
            )
            ->groupBy('settings.uid')
            ->execute();
        return (array)$this->fetchAssociative($result);
    }
}
