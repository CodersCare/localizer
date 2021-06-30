<?php

namespace Localizationteam\Localizer\Model\Repository;

use Localizationteam\Localizer\Constants;
use PDO;

/**
 * Repository for the module 'Selector' for the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class AutomaticExportRepository extends AbstractRepository
{
    /**
     * Loads available carts, which have not been finalized yet
     *
     * @param int $localizerId
     * @return array|null
     */
    public function loadUnfinishedButSentCarts($localizerId)
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_CART);
        $queryBuilder->getRestrictions();
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
                    $queryBuilder->expr()->gte(
                        'status',
                        Constants::STATUS_CART_FINALIZED
                    ),
                    $queryBuilder->expr()->lt(
                        'status',
                        Constants::STATUS_CART_FILE_IMPORTED
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
     * @return array|null
     */
    public function loadPagesConfiguredForAutomaticExport($age, $excludedPages)
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions();
        $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->gt(
                        'localizer_include_with_automatic_export',
                        0
                    ),
                    $queryBuilder->expr()->gte(
                        'status',
                        Constants::STATUS_CART_FINALIZED
                    ),
                    $queryBuilder->expr()->lt(
                        'status',
                        Constants::STATUS_CART_FILE_IMPORTED
                    )
                )
            );
        if (!empty($excludedPages)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn(
                    'uid',
                    $excludedPages
                )
            );
        }
        $pages = $queryBuilder->execute()
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
     * @return array|null
     */
    public function loadPagesAddedToSpecificAutomaticExport($localizer, $age, $excludedPages)
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable('pages');
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
                        (int)$localizer
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
            self::getConnectionPool()
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
