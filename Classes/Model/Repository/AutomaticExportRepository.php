<?php

declare(strict_types=1);

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
     */
    public function loadUnfinishedButSentCarts(int $localizerId): array
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_CART);
        $result = $queryBuilder
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
            ->execute();
        return $this->fetchAllAssociative($result);
    }

    /**
     * Loads pages that are configured to be exported autimatically based on a given age
     */
    public function loadPagesConfiguredForAutomaticExport(int $age, array $excludedPages): array
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable('pages');

        $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->and(
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
        $result = $queryBuilder->execute();
        $pages = $this->fetchAllAssociative($result);
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
     */
    public function loadPagesAddedToSpecificAutomaticExport(int $localizer, int $age, array $excludedPages): array
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable('pages');
        $result = $queryBuilder
            ->select('pages.*')
            ->from('pages')
            ->leftJoin(
                'pages',
                Constants::TABLE_LOCALIZER_SETTINGS_PAGES_MM,
                'mm',
                (string)$queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'mm.uid_local',
                        $queryBuilder->quoteIdentifier('pages.uid')
                    ),
                    $queryBuilder->expr()->eq(
                        'mm.uid_foreign',
                        $localizer
                    )
                )
            )
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->notIn(
                        'pages.uid',
                        $excludedPages
                    ),
                    $queryBuilder->expr()->isNotNull('mm.uid')
                )
            )
            ->execute();
        $pages = $this->fetchAllAssociative($result);
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
     */
    public function storeCart(array $pageIds, int $cartId, array $configuration, array $automaticTriples): void
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
                                        'cart' => $cartId,
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
                        'languageId',
                    ],
                    [
                        PDO::PARAM_INT,
                        PDO::PARAM_STR,
                        PDO::PARAM_INT,
                        PDO::PARAM_STR,
                        PDO::PARAM_INT,
                        PDO::PARAM_INT,
                    ]
                );
        }
    }
}
