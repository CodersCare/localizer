<?php

namespace Localizationteam\Localizer\Model\Repository;

use Localizationteam\Localizer\Constants;
use PDO;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for the module 'Cart' for the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 */
class CartRepository extends AbstractRepository
{
    /**
     * Loads available backend users
     *
     * @return array|NULL
     */
    public function loadAvailableUsers()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_BACKEND_USERS);
        $users = $queryBuilder
            ->select(Constants::TABLE_BACKEND_USERS . '.*')
            ->from(Constants::TABLE_BACKEND_USERS)
            ->leftJoin(
                Constants::TABLE_BACKEND_USERS,
                Constants::TABLE_LOCALIZER_CART,
                'cart',
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        Constants::TABLE_BACKEND_USERS . '.uid',
                        $queryBuilder->quoteIdentifier('cart.cruser_id')
                    ),
                    $queryBuilder->expr()->eq(
                        'cart.deleted',
                        $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'cart.hidden',
                        $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)
                    )
                )
            )
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->isNotNull('cart.uid'),
                    $queryBuilder->expr()->gt(
                        Constants::TABLE_BACKEND_USERS . '.uid',
                        $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)
                    )
                )
            )
            ->groupBy(
                Constants::TABLE_BACKEND_USERS . '.uid'
            )
            ->orderBy(
                Constants::TABLE_BACKEND_USERS . '.realName',
                Constants::TABLE_BACKEND_USERS . '.username'
            )
            ->execute()
            ->fetchAll();;
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
     * @param $id
     * @param $classes
     * @param $user
     * @return array|NULL
     */
    public function getRecordInfo($id, $classes, $user)
    {
        $user = $user === 0 || $user ? $user : $this->getBackendUser()->user['uid'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_CART);
        $queryBuilder
            ->select('uid', 'uid_local', 'uid_foreign', 'previous_status', 'action')
            ->from(Constants::TABLE_LOCALIZER_CART)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        Constants::TABLE_LOCALIZER_CART . '.uid_local',
                        $queryBuilder->createNamedParameter((int)$id, PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->gt(
                        Constants::TABLE_LOCALIZER_CART . '.status',
                        $queryBuilder->createNamedParameter(Constants::STATUS_CART_ADDED, PDO::PARAM_INT)
                    )
                )
            )
            ->orderBy(
                Constants::TABLE_LOCALIZER_CART . '.uid'
            );
        if ($user > 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    Constants::TABLE_LOCALIZER_CART . '.cruser_id',
                    $queryBuilder->createNamedParameter((int)$user, PDO::PARAM_INT)
                )
            );
        }
        $carts = $queryBuilder->execut()->fetchAll();
        $availableCarts = [];
        if (!empty($carts)) {
            foreach ($carts as $cart) {
                $availableCarts[$cart['uid']] = $cart;
            }
        }
        if (!empty($availableCarts)) {
            foreach ($availableCarts as $cartId => &$cart) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_EXPORTDATA_MM);
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
                        $queryBuilder->expr()->andX(
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
                        Constants::TABLE_LOCALIZER_LANGUAGE_MM,
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
                            $queryBuilder->createNamedParameter((int)$cart['uid_foreign'], PDO::PARAM_INT)
                        )
                    )
                    ->orderBy(
                        Constants::TABLE_EXPORTDATA_MM . '.status'
                    )
                    ->execute()
                    ->fetchAll();
                $cart['exportData'] = [];
                if (!empty($exportData)) {
                    foreach ($exportData as $data) {
                        $cart['exportData'][$data['uid']] = $data;
                    }
                }
                if (!empty($cart['exportData'])) {
                    foreach ($cart['exportData'] as $exportId => &$export) {
                        $export['locale'] = str_replace(
                            '_', '-',
                            strtolower($export['lg_collate_locale'] ? $export['lg_collate_locale'] : $export['lg_iso_2'])
                        );
                        unset($export['lg_collate_locale']);
                        unset($export['lg_iso_2']);
                        $export['cssClass'] = $classes[$export['status']]['cssClass'];
                        $export['label'] = $GLOBALS['LANG']->sL(
                            'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . $export['status']
                        );
                        if ((int)$export['status'] && ((int)$export['status'] < (int)$cart['status'] || !isset($cart['status']))) {
                            $cart['status'] = $export['status'];
                        }
                        $cart['exportCounters'][$export['status']]['cssClass'] = $classes[$export['status']]['cssClass'];
                        $cart['exportCounters'][$export['status']]['action'] = $export['action'];
                        $cart['exportCounters'][$export['status']]['label'] = $GLOBALS['LANG']->sL(
                            'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . $export['status']
                        );
                        $cart['exportCounters'][$export['status']]['counter']++;
                    }
                }
                $cart['cssClass'] = $classes[$cart['status']]['cssClass'];
                $cart['label'] = $GLOBALS['LANG']->sL(
                    'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . $cart['status']
                );
            }
        };
        return $availableCarts;
    }

}