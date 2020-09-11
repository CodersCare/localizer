<?php

namespace Localizationteam\Localizer\Model\Repository;

use Localizationteam\Localizer\Constants;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for the module 'Selector' for the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 */
class AutomaticExportRepository extends AbstractRepository
{
    /**
     * Loads available carts, which have not been finalized yet
     *
     * @param int $localizerId
     * @return array|NULL
     */
    public function loadUnfinishedButSentCarts($localizerId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_CART);
        $queryBuilder->getRestrictions();
        return $queryBuilder
            ->select('*')
            ->from(Constants::TABLE_LOCALIZER_CART)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'cruser_id',
                        $queryBuilder->createNamedParameter((int)$this->getBackendUser()->user['uid'], PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $queryBuilder->createNamedParameter((int)$localizerId, PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->gte(
                        'status',
                        $queryBuilder->createNamedParameter(Constants::STATUS_CART_FINALIZED, PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->lt(
                        'status',
                        $queryBuilder->createNamedParameter(Constants::STATUS_CART_FILE_IMPORTED, PDO::PARAM_INT)
                    )
                )
            )
            ->execute()
            ->fetchAll();
    }

    /**
     * Loads pages that are configured to be exported autimatically based on a given age
     *
     * @param int $age
     * @return array|NULL
     */
    public function loadPagesConfiguredForAutomaticExport($age, $excludedPages)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions();
        $pages = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->gt(
                        'localizer_include_with_automatic_export',
                        $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->notIn(
                        'uid',
                        $excludedPages
                    ),
                    $queryBuilder->expr()->gte(
                        'status',
                        $queryBuilder->createNamedParameter(Constants::STATUS_CART_FINALIZED, PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->lt(
                        'status',
                        $queryBuilder->createNamedParameter(Constants::STATUS_CART_FILE_IMPORTED, PDO::PARAM_INT)
                    )
                )
            )
            ->execute()
            ->fetchAll();
        $pagesConfiguredForAutomaticExport = [];
        if (!empty($pages)) {
            foreach ($pages as $page) {
                $pagesConfiguredForAutomaticExport[$page['uid']] = $page;
            }
        }
        return $pagesConfiguredForAutomaticExport;
    }

    /**
     * Loads pages that are added to be exported autimatically with a specific localizer setting based on a given age
     *
     * @param int $localizer
     * @param int $age
     * @param array $excludedPages
     * @return array|NULL
     */
    public function loadPagesAddedToSpecificAutomaticExport($localizer, $age, $excludedPages)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $pages = $queryBuilder
            ->select('pages.*')
            ->from('pages')
            ->leftJoin(
                'pages',
                Constants::TABLE_LOCALIZER_SETTINGS_PAGES_MM,
                'mm',
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'mm.uid_local',
                        $queryBuilder->quoteIdentifier('pages.uid')
                    ),
                    $queryBuilder->expr()->eq(
                        'mm.uid_foreign',
                        $queryBuilder->createNamedParameter((int)$localizer, PDO::PARAM_INT)
                    )
                )
            )
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->notIn(
                        'pages.uid',
                        $excludedPages
                    ),
                    $queryBuilder->expr()->isNotNull('mm.uid')
                )
            )
            ->execute()
            ->fetchAll();
        $pagesAddedToSpecificAutomaticExport = [];
        if (!empty($pages)) {
            foreach ($pages as $page) {
                $pagesAddedToSpecificAutomaticExport[$page['uid']] = $page;
            }
        }
        return $pagesAddedToSpecificAutomaticExport;
    }

    /**
     * Stores the items of the selected cart
     *
     * @param array $pageIds
     * @param int $cartId
     * @param array $configuration
     * @param array $automaticTriples
     */
    public function storeCart($pageIds, $cartId, $configuration, $automaticTriples)
    {
        $insertValues = [];
        $pageId = key($pageIds);
        if (!empty($automaticTriples)) {
            foreach ($automaticTriples as $tableName => $records) {
                if (!empty($records) && $configuration['tables'][$tableName]) {
                    foreach ($records as $recordId => $languages) {
                        if (!empty($languages)) {
                            foreach ($languages as $languageId => $checked) {
                                if ($configuration['languages'][$languageId]) {
                                    $identifier = md5($tableName . '.' . $recordId . '.' . $languageId);
                                    $insertValues[$identifier] = [
                                        'pid' => (int)$pageId,
                                        'identifier' => $identifier,
                                        'cart' => (int)$cartId,
                                        'tablename' => $tableName,
                                        'recordId' => (int)$recordId,
                                        'languageId' => (int)$languageId,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        if (!empty($insertValues)) {
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable(Constants::TABLE_CARTDATA_MM)
                ->bulkInsert(
                    Constants::TABLE_CARTDATA_MM,
                    $insertValues,
                    [
                        'pid',
                        'identifier',
                        'cart',
                        'tablename',
                        'recordId',
                        'languageId'
                    ],
                    [
                        PDO::PARAM_INT,
                        PDO::PARAM_STR,
                        PDO::PARAM_INT,
                        PDO::PARAM_STR,
                        PDO::PARAM_INT,
                        PDO::PARAM_INT
                    ]
                );
        }
    }

}