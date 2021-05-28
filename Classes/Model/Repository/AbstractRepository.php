<?php

namespace Localizationteam\Localizer\Model\Repository;

use Localizationteam\Localizer\BackendUser;
use Localizationteam\Localizer\Constants;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for the module 'Selector' for the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class AbstractRepository
{
    use BackendUser;

    /**
     * @param int $localizerId
     * @return array|false|null
     */
    public function getLocalizerLanguages($localizerId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_LOCALIZER_SETTINGS
        );
        $queryBuilder->getRestrictions()
            ->removeAll();
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
                    (int)$localizerId
                )
            )
            ->groupBy('settings.uid')
            ->execute()
            ->fetch();
    }

    /**
     * @param array $systemLanguages
     * @return array|false|null
     */
    public function getStaticLanguages($systemLanguages)
    {
        $systemLanguageUids = [];
        $systemLanguageUids[] = '0';
        foreach ($systemLanguages as $language) {
            $systemLanguageUids[] = (int)$language['uid'];
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_SYS_LANGUAGE
        );
        $queryBuilder->getRestrictions()
            ->removeAll();
        $languages = $queryBuilder
            ->select('*')
            ->from(Constants::TABLE_SYS_LANGUAGE)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $systemLanguageUids
                )
            )
            ->execute()
            ->fetchAll();
        $staticLanguages = [];
        if (!empty($languages)) {
            foreach ($languages as $language) {
                $staticLanguages[$language['uid']] = $language;
            }
        }
        if (!empty($staticLanguages)) {
            foreach ($systemLanguages as $language) {
                if (isset($staticLanguages[$language['uid']])) {
                    $staticLanguages[$language['uid']]['flagIcon'] = $language['flagIcon'];
                }
            }
        }

        return $staticLanguages;
    }

    /**
     * Loads the configuration of the selected cart
     *
     * @param int $cartId
     * @return array
     */
    public function loadConfiguration($cartId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_LOCALIZER_CART
        );
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
                    (int)$cartId
                )
            )
            ->execute()
            ->fetch();
        if (!empty($selectedCart['configuration'])) {
            $configuration = json_decode($selectedCart['configuration'], true);
            if (!empty($configuration)) {
                return [
                    'tables' => $configuration['tables'],
                    'languages' => $configuration['languages'],
                    'start' => $configuration['start'],
                    'end' => $configuration['end'],
                    'sortexports' => $configuration['sortexports'],
                    'plainxmlexports' => $configuration['plainxmlexports']
                ];
            }
        }
        return [];
    }

    /**
     * Loads available localizer settings
     *
     * @return array|null
     */
    public function loadAvailableLocalizers()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_LOCALIZER_SETTINGS
        );
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $localizers = $queryBuilder
            ->select('*')
            ->from(Constants::TABLE_LOCALIZER_SETTINGS)
            ->execute()
            ->fetchAll();
        $availableLocalizers = [];
        if (!empty($localizers)) {
            foreach ($localizers as $localizer) {
                $availableLocalizers[$localizer['uid']] = $localizer;
            }
        }
        return $availableLocalizers;
    }

    /**
     * Loads available carts, which have not been finalized yet
     *
     * @param int $localizerId
     * @return array|null
     */
    public function loadAvailableCarts($localizerId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_LOCALIZER_CART
        );
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        return $queryBuilder
            ->select('*')
            ->from(Constants::TABLE_LOCALIZER_CART)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'cruser_id',
                        (int)$this->getBackendUser()->user['uid']
                    ),
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        (int)$localizerId
                    ),
                    $queryBuilder->expr()->eq(
                        'status',
                        Constants::STATUS_CART_ADDED
                    )
                )
            )
            ->execute()
            ->fetchAll();
    }

    /**
     * Loads available pages for carts
     *
     * @param int $pageId
     * @param int $cartId
     * @return array|null
     */
    public function loadAvailablePages($pageId, $cartId)
    {
        $pageId = (int)$pageId;
        $cartId = (int)$cartId;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_CARTDATA_MM
        );
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $pages = $queryBuilder
            ->select('pid')
            ->from(Constants::TABLE_CARTDATA_MM)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->gt(
                        'pid',
                        0
                    ),
                    $queryBuilder->expr()->eq(
                        'cart',
                        (int)$cartId
                    )
                )
            )
            ->groupBy('pid')
            ->execute()
            ->fetchAll();
        $availablePages = [];
        if (!empty($pages)) {
            foreach ($pages as $page) {
                $availablePages[$page['pid']] = $page;
            }
        }
        if ($pageId > 0) {
            $availablePages[$pageId] = [
                'pid' => $pageId,
            ];
        }
        if (!empty($availablePages)) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
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
                ->execute()
                ->fetchAll();
            $pageTitles = [];
            if (!empty($titles)) {
                foreach ($titles as $title) {
                    $pageTitles[$title['uid']] = $title;
                }
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
     * @param int $cartId
     * @return array|null
     */
    public function loadAvailableLanguages($cartId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_CARTDATA_MM
        );
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $languages = $queryBuilder
            ->select('languageId')
            ->from(Constants::TABLE_CARTDATA_MM)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->gt(
                        'languageId',
                        0
                    ),
                    $queryBuilder->expr()->eq(
                        'cart',
                        (int)$cartId
                    )
                )
            )
            ->groupBy('languageId')
            ->execute()
            ->fetchAll();
        $availableLanguages = [];
        if (!empty($languages)) {
            foreach ($languages as $language) {
                $availableLanguages[$language['languageId']] = $language;
            }
        }
        return $availableLanguages;
    }

    /**
     * Loads available pages for carts
     *
     * @param int $cartId
     * @return array|null
     */
    public function loadAvailableTables($cartId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_CARTDATA_MM
        );
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $tables = $queryBuilder
            ->select('tableName')
            ->from(Constants::TABLE_CARTDATA_MM)
            ->where(
                $queryBuilder->expr()->eq(
                    'cart',
                    (int)$cartId
                )
            )
            ->groupBy('tableName')
            ->execute()
            ->fetchAll();
        $availableTables = [];
        if (!empty($tables)) {
            foreach ($tables as $table) {
                $availableTables[$table['tableName']] = $table;
            }
        }
        return $availableTables;
    }

    /**
     * Gets all related child records of a parent record based on the reference index
     *
     * @param array $record
     * @param int $table
     * @param array $translatableTables
     * @return array $relations
     */
    protected function checkRelations($record, $table, $translatableTables)
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
                /**@var $relationHandler RelationHandler * */
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
