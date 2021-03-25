<?php

namespace Localizationteam\Localizer\Model\Repository;

use Localizationteam\Localizer\Constants;
use TYPO3\CMS\Backend\Utility\BackendUtility;
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
        $unfinishedButSentCarts = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            Constants::TABLE_LOCALIZER_CART,
            'cruser_id = ' . $this->getBackendUser()->user['uid'] .
            ' AND uid_local = ' . (int)$localizerId .
            ' AND status >= ' . Constants::STATUS_CART_FINALIZED .
            ' AND status < ' . Constants::STATUS_CART_FILE_IMPORTED .
            BackendUtility::BEenableFields(Constants::TABLE_LOCALIZER_CART) . BackendUtility::deleteClause(Constants::TABLE_LOCALIZER_CART)
        );
        return $unfinishedButSentCarts;
    }

    /**
     * Loads pages that are configured to be exported autimatically based on a given age
     *
     * @param int $age
     * @return array|NULL
     */
    public function loadPagesConfiguredForAutomaticExport($age, $excludedPages)
    {
        $safeExcludedPageUids = implode(',', GeneralUtility::intExplode(',', implode(',', $excludedPages)));
        $age = time() - $age * 60;
        $pagesConfiguredForAutomaticExport = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            'pages',
            'localizer_include_with_automatic_export > 0' .
            $safeExcludedPageUids ? (' AND uid NOT IN (' . $safeExcludedPageUids . ') ') : ' ' .
            BackendUtility::BEenableFields('pages') . BackendUtility::deleteClause('pages'),
            '',
            '',
            '',
            'uid'
        );
        return $pagesConfiguredForAutomaticExport;
    }

    /**
     * Loads pages that are added to be exported autimatically with a specific localizer setting based on a given age
     *
     * @param int $localizer
     * @param int $age
     * @return array|NULL
     */
    public function loadPagesAddedToSpecificAutomaticExport($localizer, $age, $excludedPages)
    {
        $safeExcludedPageUids = implode(',', GeneralUtility::intExplode(',', implode(',', $excludedPages)));
        $age = time() - $age * 60;
        $pagesAddedToSpecificAutomaticExport = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'pages.*',
            'pages ' .
            'LEFT OUTER JOIN ' . Constants::TABLE_LOCALIZER_SETTINGS_PAGES_MM . ' mm 
              ON mm.uid_local = pages.uid AND mm.uid_foreign = ' . (int)$localizer,
            'pages.uid NOT IN (' . $safeExcludedPageUids . ') AND mm.uid IS NOT NULL ' .
            BackendUtility::BEenableFields('pages') . BackendUtility::deleteClause('pages'),
            '',
            '',
            '',
            'uid'
        );
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
                                        'pid'        => (int)$pageId,
                                        'identifier' => $identifier,
                                        'cart'       => (int)$cartId,
                                        'tablename'  => $tableName,
                                        'recordId'   => (int)$recordId,
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
            $this->getDatabaseConnection()
                ->exec_INSERTmultipleRows(
                    Constants::TABLE_CARTDATA_MM,
                    ['pid', 'identifier', 'cart', 'tablename', 'recordId', 'languageId'],
                    $insertValues,
                    'cart,recordId,languageId'
                );
        }
    }

}