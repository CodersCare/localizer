<?php

namespace Localizationteam\Localizer\Hooks;

use Localizationteam\L10nmgr\View\PostSaveInterface;
use Localizationteam\Localizer\AddFileToMatrix;
use Localizationteam\Localizer\Constants;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * L10nMgrExportHandler
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class L10nMgrExportHandler implements PostSaveInterface
{
    use AddFileToMatrix;

    /**
     * @param array $params
     */
    public function postExportAction(array $params)
    {
        if ($params['data']['exportType'] == 1) { //XML
            if ($params['data']['source_lang'] != $params['data']['translation_lang']) {
                if ($_REQUEST['export_xml_forcepreviewlanguage'] != $_REQUEST['SET']['lang']) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
                        Constants::TABLE_LOCALIZER_SETTINGS
                    );
                    $row = $queryBuilder
                        ->select(
                            Constants::TABLE_LOCALIZER_SETTINGS . '.uid',
                            Constants::TABLE_LOCALIZER_SETTINGS . '.pid',
                            Constants::TABLE_LOCALIZER_SETTINGS . '.project_settings',
                            'source_locale',
                            'target_locale'
                        )
                        ->from(Constants::TABLE_LOCALIZER_SETTINGS)
                        ->join(
                            Constants::TABLE_LOCALIZER_SETTINGS,
                            Constants::TABLE_LOCALIZER_L10NMGR_MM,
                            'mm'
                        )
                        ->join(
                            'mm',
                            Constants::TABLE_L10NMGR_CONFIGURATION,
                            Constants::TABLE_L10NMGR_CONFIGURATION
                        )
                        ->where(
                            $queryBuilder->expr()->andX(
                                $queryBuilder->expr()->eq(
                                    Constants::TABLE_LOCALIZER_SETTINGS . '.uid',
                                    $queryBuilder->quoteIdentifier('mm.uid_local')
                                ),
                                $queryBuilder->expr()->eq(
                                    Constants::TABLE_L10NMGR_CONFIGURATION . '.uid',
                                    $queryBuilder->quoteIdentifier('mm.uid_foreign')
                                ),
                                $queryBuilder->expr()->eq(
                                    'mm.uid_foreign',
                                    (int)$params['data']['l10ncfg_id']
                                ),
                                $queryBuilder->expr()->in(
                                    Constants::TABLE_LOCALIZER_SETTINGS . '.pid',
                                    $this->getRootline(
                                        $this->getSrcPid()
                                    )
                                )
                            )
                        )
                        ->setMaxResults(1)
                        ->execute()
                        ->fetch();
                    if ($row['pid'] !== null) {
                        $this->addFileToMatrix(
                            $row['pid'],
                            $row['uid'],
                            $params['uid'],
                            $params['data']['l10ncfg_id'],
                            $params['data']['filename'],
                            $params['data']['translation_lang']
                        );
                    }
                }
            }
        }
    }

    /**
     * @param int $uid
     * @return array
     */
    protected function getRootline(
        $uid
    ) {
        $rootLineList = BackendUtility::BEgetRootLine($uid);
        $rootLine = [];
        foreach ($rootLineList as $page) {
            $rootLine[] = $page['uid'];
        }
        unset($rootLineList);
        return $rootLine;
    }

    /**
     * @return int
     */
    protected function getSrcPid()
    {
        return (int)$_GET['srcPID'];
    }
}
