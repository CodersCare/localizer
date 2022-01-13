<?php

namespace Localizationteam\Localizer\Model\Repository;

use Localizationteam\Localizer\Constants;
use PDO;
use TYPO3\CMS\Core\Database\Connection;

class LocalizerSettingsRepository extends AbstractRepository
{
    /**
     * @param int $uid
     * @param array $fields
     * @return array
     */
    public function findByUid(int $uid, array $fields = ['*']): array
    {
        $connection = self::getConnectionForTable(Constants::TABLE_LOCALIZER_SETTINGS);
        return $connection->select($fields, Constants::TABLE_LOCALIZER_SETTINGS, ['uid' => $uid])->fetchAssociative();
    }

    public static function getConnectionForTable($table): Connection
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
        return $connection->select(['*'], Constants::TABLE_LOCALIZER_SETTINGS)->fetchAllAssociative();
    }

    /**
     * @param int $localizerId
     * @return array|false|null
     * @todo Make the return type an array in any case to be able to add return type
     */
    public function getLocalizerLanguages(int $localizerId)
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_SETTINGS);
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->selectLiteral('MAX(sourceLanguage.uid) source, GROUP_CONCAT(targetLanguage.uid) target')
            ->from(Constants::TABLE_LOCALIZER_SETTINGS, 'settings')
            ->leftJoin(
                'settings',
                Constants::TABLE_LOCALIZER_LANGUAGE_MM,
                'sourceMM',
                $queryBuilder->expr()->andX(
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
                $queryBuilder->expr()->eq(
                    'sourceLanguage.uid',
                    $queryBuilder->quoteIdentifier('sourceMM.uid_foreign')
                )
            )
            ->leftJoin(
                'settings',
                Constants::TABLE_LOCALIZER_LANGUAGE_MM,
                'targetMM',
                $queryBuilder->expr()->andX(
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
                $queryBuilder->expr()->eq(
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
            ->execute()
            ->fetchAssociative();
    }
}
