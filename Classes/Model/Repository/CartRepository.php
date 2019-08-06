<?php

namespace Localizationteam\Localizer\Model\Repository;

use Localizationteam\Localizer\Constants;
use TYPO3\CMS\Backend\Utility\BackendUtility;

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
        $availableUsers = $this->getDatabaseConnection()->exec_SELECTgetRows(
            Constants::TABLE_BACKEND_USERS . '.*',
            Constants::TABLE_BACKEND_USERS .
            ' LEFT OUTER JOIN ' . Constants::TABLE_LOCALIZER_CART . ' cart' .
            ' ON ' . Constants::TABLE_BACKEND_USERS . '.uid = cart.cruser_id 
                AND cart.deleted = 0 AND cart.hidden = 0',
            'cart.uid IS NOT NULL AND ' .
            Constants::TABLE_BACKEND_USERS . '.uid > 0' .
            BackendUtility::BEenableFields(Constants::TABLE_BACKEND_USERS) . BackendUtility::deleteClause(Constants::TABLE_BACKEND_USERS),
            Constants::TABLE_BACKEND_USERS . '.uid',
            Constants::TABLE_BACKEND_USERS . '.realName, ' . Constants::TABLE_BACKEND_USERS . '.username',
            '',
            'uid'
        );
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
        $availableCarts = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid, uid_local, uid_foreign, previous_status, action',
            Constants::TABLE_LOCALIZER_CART,
            Constants::TABLE_LOCALIZER_CART . '.uid_local = ' . (int)$id .
            ($user > 0 ? (' AND ' . Constants::TABLE_LOCALIZER_CART . '.cruser_id = ' . (int)$user) : '') .
            ' AND ' . Constants::TABLE_LOCALIZER_CART . '.status > ' . Constants::STATUS_CART_ADDED .
            BackendUtility::BEenableFields(Constants::TABLE_LOCALIZER_CART) .
            BackendUtility::deleteClause(Constants::TABLE_LOCALIZER_CART),
            '',
            Constants::TABLE_LOCALIZER_CART . '.uid',
            '',
            'uid'
        );
        if (!empty($availableCarts)) {
            foreach ($availableCarts as $cartId => &$cart) {
                $cart['exportData'] = $this->getDatabaseConnection()->exec_SELECTgetRows(
                    Constants::TABLE_EXPORTDATA_MM . '.uid, ' .
                    Constants::TABLE_EXPORTDATA_MM . '.uid_local, ' .
                    Constants::TABLE_EXPORTDATA_MM . '.uid_foreign, ' .
                    Constants::TABLE_EXPORTDATA_MM . '.status, ' .
                    Constants::TABLE_EXPORTDATA_MM . '.previous_status, ' .
                    Constants::TABLE_EXPORTDATA_MM . '.action, ' .
                    Constants::TABLE_EXPORTDATA_MM . '.filename, ' .
                    Constants::TABLE_STATIC_LANGUAGES . '.lg_collate_locale, ' .
                    Constants::TABLE_STATIC_LANGUAGES . '.lg_iso_2',
                    Constants::TABLE_EXPORTDATA_MM .
                    ' LEFT OUTER JOIN ' . Constants::TABLE_LOCALIZER_LANGUAGE_MM . ' targetMM' .
                    ' ON ' . Constants::TABLE_EXPORTDATA_MM . '.uid = targetMM.uid_local 
                            AND targetMM.tablenames = "' . Constants::TABLE_STATIC_LANGUAGES . '" 
                            AND targetMM.ident = "target"
                            AND targetMM.source = "' . Constants::TABLE_EXPORTDATA_MM . '"' .
                    ' LEFT OUTER JOIN ' . Constants::TABLE_STATIC_LANGUAGES .
                    ' ON ' . Constants::TABLE_STATIC_LANGUAGES . '.uid = targetMM.uid_foreign',
                    Constants::TABLE_EXPORTDATA_MM . ' .uid_foreign = ' . (int)$cart['uid_foreign'] .
                    BackendUtility::BEenableFields(Constants::TABLE_EXPORTDATA_MM) .
                    BackendUtility::deleteClause(Constants::TABLE_EXPORTDATA_MM),
                    '',
                    Constants::TABLE_EXPORTDATA_MM . '.status ASC',
                    '',
                    'uid'
                );
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