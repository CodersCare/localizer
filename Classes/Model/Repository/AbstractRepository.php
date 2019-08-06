<?php

namespace Localizationteam\Localizer\Model\Repository;

use Localizationteam\Localizer\BackendUser;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\DatabaseConnection;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Repository for the module 'Selector' for the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 */
class AbstractRepository
{
    use BackendUser, DatabaseConnection;

    /**
     * @param $localizerId
     * @return array|FALSE|NULL
     */
    public function getLocalizerLanguages($localizerId)
    {
        return $this->getDatabaseConnection()
            ->exec_SELECTgetSingleRow(
                'MAX(sourceLanguage.uid) source, GROUP_CONCAT(targetLanguage.uid) target',
                Constants::TABLE_LOCALIZER_SETTINGS . ' settings' .
                ' LEFT OUTER JOIN ' . Constants::TABLE_LOCALIZER_LANGUAGE_MM . ' sourceMM' .
                ' ON settings.uid = sourceMM.uid_local 
                            AND sourceMM.tablenames = "' . Constants::TABLE_STATIC_LANGUAGES . '" 
                            AND sourceMM.ident = "source"
                            AND sourceMM.source = "' . Constants::TABLE_LOCALIZER_SETTINGS . '"' .
                ' LEFT OUTER JOIN ' . Constants::TABLE_STATIC_LANGUAGES . ' sourceLanguage ON sourceLanguage.uid = sourceMM.uid_foreign' .
                ' LEFT OUTER JOIN ' . Constants::TABLE_LOCALIZER_LANGUAGE_MM . ' targetMM' .
                ' ON settings.uid = targetMM.uid_local 
                            AND targetMM.tablenames = "' . Constants::TABLE_STATIC_LANGUAGES . '" 
                            AND targetMM.ident = "target"
                            AND targetMM.source = "' . Constants::TABLE_LOCALIZER_SETTINGS . '"' .
                ' LEFT OUTER JOIN ' . Constants::TABLE_STATIC_LANGUAGES . ' targetLanguage ON targetLanguage.uid = targetMM.uid_foreign',
                'settings.uid = ' . (int)$localizerId,
                'settings.uid'
            );
    }

    /**
     * Loads the configuration of the selected cart
     *
     * @param $cartId
     * @return array
     */
    public function loadConfiguration($cartId)
    {
        $selectedCart = $this->getDatabaseConnection()
            ->exec_SELECTgetSingleRow(
                '*',
                Constants::TABLE_LOCALIZER_CART,
                'uid = ' . (int)$cartId
            );
        if (!empty($selectedCart['configuration'])) {
            $configuration = json_decode($selectedCart['configuration'], true);
            if (!empty($configuration)) {
                return [
                    'tables'    => $configuration['tables'],
                    'languages' => $configuration['languages'],
                    'start'     => $configuration['start'],
                    'end'       => $configuration['end'],
                ];
            }
        }
        return [];
    }

    /**
     * Loads available localizer settings
     *
     * @return array|NULL
     */
    public function loadAvailableLocalizers()
    {
        $availableLocalizeres = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            Constants::TABLE_LOCALIZER_SETTINGS,
            'uid > 0 ' . BackendUtility::BEenableFields(Constants::TABLE_LOCALIZER_SETTINGS) . BackendUtility::deleteClause(Constants::TABLE_LOCALIZER_SETTINGS),
            '',
            '',
            '',
            'uid'
        );
        return $availableLocalizeres;
    }

    /**
     * Loads available carts, which have not been finalized yet
     *
     * @param int $localizerId
     * @return array|NULL
     */
    public function loadAvailableCarts($localizerId)
    {
        $availableCarts = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            Constants::TABLE_LOCALIZER_CART,
            'cruser_id = ' . $this->getBackendUser()->user['uid'] .
            ' AND uid_local = ' . (int)$localizerId .
            ' AND status = ' . Constants::STATUS_CART_ADDED . BackendUtility::BEenableFields(Constants::TABLE_LOCALIZER_CART) . BackendUtility::deleteClause(Constants::TABLE_LOCALIZER_CART)
        );
        return $availableCarts;
    }

    /**
     * Loads available pages for carts
     *
     * @param int $pageId
     * @param int $cartId
     * @return array|NULL
     */
    public function loadAvailablePages($pageId, $cartId)
    {
        $pageId = (int)$pageId;
        $cartId = (int)$cartId;
        $availablePages = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'DISTINCT pid',
            Constants::TABLE_CARTDATA_MM,
            'pid > 0 AND cart = ' . $cartId,
            '',
            '',
            '',
            'pid'
        );
        if ($pageId > 0) {
            $availablePages[$pageId] = [
                'pid' => $pageId,
            ];
        }
        if (!empty($availablePages)) {
            $pageTitles = $this->getDatabaseConnection()->exec_SELECTgetRows(
                'uid,title',
                'pages',
                'uid IN (' . implode(',', array_keys($availablePages)) . ')',
                '',
                '',
                '',
                'uid'
            );
            foreach ($availablePages as $pageId => &$pageData) {
                $pageData['title'] = $pageTitles[$pageId]['title'];
            }
        }
        return $availablePages;
    }

    /**
     * Loads available pages for carts
     *
     * @param int $cartId
     * @return array|NULL
     */
    public function loadAvailableLanguages($cartId)
    {
        $availableLanguages = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'DISTINCT languageId',
            Constants::TABLE_CARTDATA_MM,
            'languageId > 0 AND cart = ' . (int)$cartId,
            '',
            '',
            '',
            'languageId'
        );
        return $availableLanguages;
    }

    /**
     * Loads available pages for carts
     *
     * @param int $cartId
     * @return array|NULL
     */
    public function loadAvailableTables($cartId)
    {
        $availableTables = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'DISTINCT tablename',
            Constants::TABLE_CARTDATA_MM,
            'cart = ' . (int)$cartId,
            '',
            '',
            '',
            'tablename'
        );
        return $availableTables;
    }

    /**
     * Gets all related child records of a parent record based on the reference index
     *
     * @param int $uid
     * @param int $table
     * @return array|NULL
     */
    protected function checkReferences($uid, $table)
    {
        $table = $this->getDatabaseConnection()->fullQuoteStr($table, $table);
        $references = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            'sys_refindex',
            'ref_table = ' . $table . ' AND ref_uid = ' . (int)$uid . ' AND tablename != \'sys_category\''
        );
        return $references;
    }
}