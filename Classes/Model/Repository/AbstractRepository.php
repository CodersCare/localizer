<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Model\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Traits\BackendUserTrait;
use Localizationteam\Localizer\Traits\ConnectionPoolTrait;
use Localizationteam\Localizer\Traits\Data;
use PDO;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for the module 'Selector' for the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class AbstractRepository
{
    use BackendUserTrait;
    use ConnectionPoolTrait;
    use Data;

    protected Typo3Version $typo3Version;

    public function __construct()
    {
        $this->typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    public function getLocalizerLanguages(int $localizerId): array
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_SETTINGS);
        $queryBuilder->getRestrictions()->removeAll();

        if ($this->typo3Version->getMajorVersion() < 12) {
            $result = $queryBuilder
                ->selectLiteral('MAX(sourceLanguage.uid) source, GROUP_CONCAT(targetLanguage.uid) target')
                ->from(Constants::TABLE_LOCALIZER_SETTINGS, 'settings')
                ->leftJoin(
                    'settings',
                    Constants::TABLE_LOCALIZER_LANGUAGE_MM,
                    'sourceMM',
                    (string)$queryBuilder->expr()->and(
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
                    (string)$queryBuilder->expr()->and(
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
                ->executeQuery()
                ->fetchAssociative();

            return (array)$result;
        }

        $result = $queryBuilder
            ->select('source_language', 'target_languages')
            ->from(Constants::TABLE_LOCALIZER_SETTINGS, 'settings')
            ->where(
                $queryBuilder->expr()->eq(
                    'settings.uid',
                    $localizerId
                )
            )
            ->groupBy('settings.uid')
            ->executeQuery()
            ->fetchAssociative();

        return [
            'source' => $result['source_language'],
            'target' => $result['target_languages'],
        ];
    }

    /**
     * Loads the configuration of the selected cart
     *
     * @throws DBALException
     * @throws Exception
     */
    public function loadConfiguration(int $cartId): array
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_CART);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $selectedCart = $queryBuilder
            ->select('*')
            ->from(Constants::TABLE_LOCALIZER_CART)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $cartId
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        if (!empty($selectedCart['configuration'])) {
            $configuration = json_decode($selectedCart['configuration'], true);
            if (!empty($configuration)) {
                return [
                    'tables' => $configuration['tables'],
                    'languages' => $configuration['languages'],
                    'start' => $configuration['start'],
                    'end' => $configuration['end'],
                    'sortexports' => $configuration['sortexports'],
                    'plainxmlexports' => $configuration['plainxmlexports'],
                ];
            }
        }
        return [];
    }

    /**
     * Loads available localizer settings
     *
     * @throws DBALException
     * @throws Exception
     */
    public function loadAvailableLocalizers(): array
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_SETTINGS);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $localizers = $queryBuilder
            ->select('*')
            ->from(Constants::TABLE_LOCALIZER_SETTINGS)
            ->executeQuery()
            ->fetchAllAssociative();

        $availableLocalizers = [];
        foreach ($localizers as $localizer) {
            $availableLocalizers[$localizer['uid']] = $localizer;
        }
        return $availableLocalizers;
    }

    /**
     * Loads available carts, which have not been finalized yet
     *
     * @throws DBALException
     * @throws Exception
     */
    public function loadAvailableCarts(int $localizerId): array
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_CART);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));

        return $queryBuilder
            ->select('*')
            ->from(Constants::TABLE_LOCALIZER_CART)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'cruser_id',
                        (int)$this->getBackendUser()->getUserId()
                    ),
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $localizerId
                    ),
                    $queryBuilder->expr()->eq(
                        'status',
                        Constants::STATUS_CART_ADDED
                    )
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Loads available pages for carts
     *
     * @throws DBALException
     * @throws Exception
     */
    public function loadAvailablePages(int $pageId, int $cartId): array
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_CARTDATA_MM);
        $pages = $queryBuilder
            ->select('pid')
            ->from(Constants::TABLE_CARTDATA_MM)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->gt(
                        'pid',
                        0
                    ),
                    $queryBuilder->expr()->eq(
                        'cart',
                        $cartId
                    )
                )
            )
            ->groupBy('pid')
            ->executeQuery()
            ->fetchAllAssociative();

        $availablePages = [];

        foreach ($pages as $page) {
            $availablePages[$page['pid']] = $page;
        }

        if ($pageId > 0) {
            $availablePages[$pageId] = [
                'pid' => $pageId,
            ];
        }
        if (!empty($availablePages)) {
            $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $titles = $queryBuilder
                ->select('uid', 'title')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        implode(',', array_keys($availablePages))
                    )
                )
                ->executeQuery()
                ->fetchAllAssociative();

            $pageTitles = [];

            foreach ($titles as $title) {
                $pageTitles[$title['uid']] = $title;
            }

            foreach ($availablePages as $pageId => &$pageData) {
                $pageData['cart'] = $cartId;
                $pageData['title'] = $pageTitles[$pageId]['title'];
            }
        }
        return $availablePages;
    }

    /**
     * Loads available pages for carts
     *
     */
    public function loadAvailableLanguages(int $cartId): array
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_CARTDATA_MM);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $languages = $queryBuilder
            ->select('languageId')
            ->from(Constants::TABLE_CARTDATA_MM)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->gt(
                        'languageId',
                        0
                    ),
                    $queryBuilder->expr()->eq(
                        'cart',
                        $cartId
                    )
                )
            )
            ->groupBy('languageId')
            ->executeQuery()
            ->fetchAllAssociative();

        $availableLanguages = [];

        foreach ($languages as $language) {
            $availableLanguages[$language['languageId']] = $language;
        }

        return $availableLanguages;
    }

    /**
     * Loads available pages for carts
     *
     */
    public function loadAvailableTables(int $cartId): array
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_CARTDATA_MM);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $tables = $queryBuilder
            ->select('tableName')
            ->from(Constants::TABLE_CARTDATA_MM)
            ->where(
                $queryBuilder->expr()->eq(
                    'cart',
                    $cartId
                )
            )
            ->groupBy('tableName')
            ->executeQuery()
            ->fetchAllAssociative();

        $availableTables = [];

        foreach ($tables as $table) {
            $availableTables[$table['tableName']] = $table;
        }

        return $availableTables;
    }

    /**
     * Gets all related child records of a parent record based on the reference index
     */
    protected function checkRelations(array $record, string $table, array $translatableTables): array
    {
        $relations = [];
        foreach ($GLOBALS['TCA'][$table]['columns'] as $fieldName => $column) {
            $configuration = $column['config'];
            if (
                (
                    $configuration['type'] === 'inline'
                    || $configuration['type'] === 'group'
                    || $configuration['type'] === 'select'
                )
                && (
                    !empty($configuration['foreign_table'])
                    || !empty($configuration['MM'])
                )
                && isset($translatableTables[$configuration['foreign_table']])
            ) {
                if (empty($record[$fieldName])) {
                    continue;
                }
                /** @var RelationHandler $relationHandler */
                $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
                $relationHandler->start(
                    $fieldName,
                    $configuration['foreign_table'],
                    $configuration['MM'] ?? '',
                    $record['uid'],
                    $table,
                    $configuration
                );
                if (!empty($relationHandler->tableArray[$configuration['foreign_table']])) {
                    $relationHandler->getFromDB();
                    $relations = array_merge_recursive($relations, $relationHandler->results);
                }
            }
        }

        return $relations;
    }
}
