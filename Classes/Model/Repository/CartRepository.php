<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Model\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Localizationteam\Localizer\Constants;
use PDO;

/**
 * Repository for the module 'Cart' for the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class CartRepository extends AbstractRepository
{
    /**
     * Loads available backend users
     */
    public function loadAvailableUsers(): array
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_BACKEND_USERS);
        $result = $queryBuilder
            ->select(Constants::TABLE_BACKEND_USERS . '.*')
            ->from(Constants::TABLE_BACKEND_USERS)
            ->leftJoin(
                Constants::TABLE_BACKEND_USERS,
                Constants::TABLE_LOCALIZER_CART,
                'cart',
                (string)$queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        Constants::TABLE_BACKEND_USERS . '.uid',
                        $queryBuilder->quoteIdentifier('cart.cruser_id')
                    ),
                    $queryBuilder->expr()->eq(
                        'cart.deleted',
                        0
                    ),
                    $queryBuilder->expr()->eq(
                        'cart.hidden',
                        0
                    )
                )
            )
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->isNotNull('cart.uid'),
                    $queryBuilder->expr()->gt(
                        Constants::TABLE_BACKEND_USERS . '.uid',
                        0
                    )
                )
            )
            ->groupBy(
                Constants::TABLE_BACKEND_USERS . '.uid'
            )
            ->orderBy(
                Constants::TABLE_BACKEND_USERS . '.realName'
            )
            ->addOrderBy(
                Constants::TABLE_BACKEND_USERS . '.username'
            )
            ->execute();
        $users = $this->fetchAllAssociative($result);
        $availableUsers = [];
        if (!empty($users)) {
            foreach ($users as $user) {
                $availableUsers[$user['uid']] = $user;
            }
        }
        return $availableUsers;
    }

    /**
     * Loads additional information about the listed cart records
     *
     * @throws DBALException
     * @throws Exception
     */
    public function getRecordInfo(int $id, array $classes, int $userId): array
    {
        if ($userId === 0) {
            $userId = (int)$this->getBackendUser()->getUserId();
        }

        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_CART);
        $queryBuilder
            ->select('uid', 'uid_local', 'uid_foreign', 'previous_status', 'action')
            ->from(Constants::TABLE_LOCALIZER_CART)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        Constants::TABLE_LOCALIZER_CART . '.uid_local',
                        $id
                    ),
                    $queryBuilder->expr()->gt(
                        Constants::TABLE_LOCALIZER_CART . '.status',
                        Constants::STATUS_CART_ADDED
                    )
                )
            )
            ->orderBy(
                Constants::TABLE_LOCALIZER_CART . '.uid'
            );

        if ($userId > 0) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder
                        ->expr()
                        ->eq(Constants::TABLE_LOCALIZER_CART . '.cruser_id', $userId)
                );
        }
        $carts = $queryBuilder
            ->executeQuery()
            ->fetchAllAssociative();

        $availableCarts = [];
        foreach ($carts as $cart) {
            $availableCarts[$cart['uid']] = $cart;
        }

        if (!empty($availableCarts)) {
            foreach ($availableCarts as $cartId => &$cart) {
                $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_EXPORTDATA_MM);
                $exportData = $queryBuilder
                    ->select(
                        Constants::TABLE_EXPORTDATA_MM . '.uid',
                        Constants::TABLE_EXPORTDATA_MM . '.uid_local',
                        Constants::TABLE_EXPORTDATA_MM . '.uid_foreign',
                        Constants::TABLE_EXPORTDATA_MM . '.status',
                        Constants::TABLE_EXPORTDATA_MM . '.previous_status',
                        Constants::TABLE_EXPORTDATA_MM . '.action',
                        Constants::TABLE_EXPORTDATA_MM . '.filename',
                        Constants::TABLE_STATIC_LANGUAGES . '.lg_collate_locale',
                        Constants::TABLE_STATIC_LANGUAGES . '.lg_iso_2'
                    )
                    ->from(Constants::TABLE_EXPORTDATA_MM)
                    ->leftJoin(
                        Constants::TABLE_EXPORTDATA_MM,
                        Constants::TABLE_LOCALIZER_LANGUAGE_MM,
                        'targetMM',
                        (string)$queryBuilder->expr()->andX(
                            $queryBuilder->expr()->eq(
                                Constants::TABLE_EXPORTDATA_MM . '.uid',
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
                                $queryBuilder->createNamedParameter(Constants::TABLE_EXPORTDATA_MM, PDO::PARAM_STR)
                            )
                        )
                    )
                    ->leftJoin(
                        'targetMM',
                        Constants::TABLE_STATIC_LANGUAGES,
                        Constants::TABLE_STATIC_LANGUAGES,
                        $queryBuilder->expr()->eq(
                            Constants::TABLE_STATIC_LANGUAGES . '.uid',
                            $queryBuilder->quoteIdentifier('targetMM.uid_foreign')
                        )
                    )
                    ->where(
                        $queryBuilder->expr()->eq(
                            Constants::TABLE_EXPORTDATA_MM . '.uid_foreign',
                            (int)$cart['uid_foreign']
                        )
                    )
                    ->orderBy(
                        Constants::TABLE_EXPORTDATA_MM . '.status'
                    )
                    ->executeQuery()
                    ->fetchAllAssociative();

                $cart['exportData'] = [];

                foreach ($exportData as $data) {
                    $cart['exportData'][$data['uid']] = $data;
                }

                if (!empty($cart['exportData'])) {
                    foreach ($cart['exportData'] as $exportId => &$export) {
                        $export['locale'] = str_replace(
                            '_',
                            '-',
                            strtolower(
                                (string)($export['lg_collate_locale'] ?: $export['lg_iso_2'])
                            )
                        );
                        unset($export['lg_collate_locale']);
                        unset($export['lg_iso_2']);
                        $export['cssClass'] = $classes[$export['status']]['cssClass'];
                        $export['label'] = $GLOBALS['LANG']->sL(
                            'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . $export['status']
                        );
                        if ((int)($export['status'] ?? 0) && ((int)($export['status'] ?? 0) < (int)($cart['status'] ?? 0) || !isset($cart['status']))) {
                            $cart['status'] = $export['status'];
                        }
                        $cart['exportCounters'][$export['status']]['cssClass'] = $classes[$export['status']]['cssClass'];
                        $cart['exportCounters'][$export['status']]['action'] = $export['action'];
                        $cart['exportCounters'][$export['status']]['label'] = $GLOBALS['LANG']->sL(
                            'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . $export['status']
                        );
                        $cart['exportCounters'][$export['status']]['counter'] ??= 0;
                        $cart['exportCounters'][$export['status']]['counter']++;
                    }
                }
                $cart['cssClass'] = $classes[$cart['status'] ?? 0]['cssClass'] ?? '';
                $cart['label'] = $GLOBALS['LANG']->sL(
                    'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . ($cart['status'] ?? 0)
                );
            }
        }
        return $availableCarts;
    }
}
