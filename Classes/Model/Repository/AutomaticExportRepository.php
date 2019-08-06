<?php

namespace Localizationteam\Localizer\Model\Repository;

use Localizationteam\Localizer\Constants;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for the module 'Selector' for the 'localizer' extension.
 *
 * @author    Jo Hasenau <info@cybercraft.de>
 * @package    TYPO3
 * @subpackage    localizer
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
            'localizer_include_with_automatic_export > 0 AND uid NOT IN (' . $safeExcludedPageUids . ') ' .
            BackendUtility::BEenableFields('pages') . BackendUtility::deleteClause('pages'),
            '',
            '',
            '',
            'uid'
        );
        return $pagesConfiguredForAutomaticExport;
    }
}