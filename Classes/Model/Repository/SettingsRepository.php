<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Model\Repository;

use Doctrine\DBAL\Exception;
use Localizationteam\Localizer\Constants;
use PDO;
use TYPO3\CMS\Core\Database\Connection;

class SettingsRepository extends AbstractRepository
{

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findByUid(int $uid, array $fields = ['*']): array
    {
        $connection = self::getConnectionForTable(Constants::TABLE_LOCALIZER_SETTINGS);

        return $connection
            ->select($fields, Constants::TABLE_LOCALIZER_SETTINGS, ['uid' => $uid])
            ->fetchAssociative();
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

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     */
    public function findAll(): array
    {
        $connection = self::getConnectionForTable(Constants::TABLE_LOCALIZER_SETTINGS);
        return $connection
            ->select(['*'], Constants::TABLE_LOCALIZER_SETTINGS)
            ->fetchAllAssociative();
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
                (string) $queryBuilder
                    ->expr()
                    ->and(
                        $queryBuilder->expr()->eq('settings.uid', $queryBuilder->quoteIdentifier('sourceMM.uid_local')),
                        $queryBuilder->expr()->eq('sourceMM.tablenames', $queryBuilder->createNamedParameter(Constants::TABLE_STATIC_LANGUAGES, PDO::PARAM_STR)),
                        $queryBuilder->expr()->eq('sourceMM.ident', $queryBuilder->createNamedParameter('source', PDO::PARAM_STR)),
                        $queryBuilder->expr()->eq('sourceMM.source', $queryBuilder->createNamedParameter(Constants::TABLE_LOCALIZER_SETTINGS, PDO::PARAM_STR))
                    )
            )
            ->leftJoin(
                'sourceMM',
                Constants::TABLE_STATIC_LANGUAGES,
                'sourceLanguage',
                $queryBuilder
                    ->expr()
                    ->eq('sourceLanguage.uid', $queryBuilder->quoteIdentifier('sourceMM.uid_foreign'))
            )
            ->leftJoin(
                'settings',
                Constants::TABLE_LOCALIZER_LANGUAGE_MM,
                'targetMM',
                (string) $queryBuilder
                    ->expr()
                    ->and(
                        $queryBuilder->expr()->eq('settings.uid', $queryBuilder->quoteIdentifier('targetMM.uid_local')),
                        $queryBuilder->expr()->eq('targetMM.tablenames', $queryBuilder->createNamedParameter(Constants::TABLE_STATIC_LANGUAGES, PDO::PARAM_STR)),
                        $queryBuilder->expr()->eq('targetMM.ident', $queryBuilder->createNamedParameter('target', PDO::PARAM_STR)),
                        $queryBuilder->expr()->eq('targetMM.source', $queryBuilder->createNamedParameter(Constants::TABLE_LOCALIZER_SETTINGS, PDO::PARAM_STR))
                    )
            )
            ->leftJoin(
                'targetMM',
                Constants::TABLE_STATIC_LANGUAGES,
                'targetLanguage',
                $queryBuilder
                    ->expr()
                    ->eq('targetLanguage.uid', $queryBuilder->quoteIdentifier('targetMM.uid_foreign'))
            )
            ->where(
                $queryBuilder->expr()->eq('settings.uid', $localizerId)
            )
            ->groupBy('settings.uid')
            ->executeQuery()
            ->fetchAssociative();

        return (array) $result;
    }
}
