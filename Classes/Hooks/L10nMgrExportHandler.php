<?php

namespace Localizationteam\Localizer\Hooks;

use Localizationteam\L10nmgr\View\PostSaveInterface;
use Localizationteam\Localizer\AddFileToMatrix;
use Localizationteam\Localizer\Constants;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * L10nMgrExportHandler $COMMENT$
 *
 * @author      Peter Russ<peter.russ@4many.net>
 * @package     TYPO3
 * @date        20150909-2150
 * @subpackage  localizer
 *
 */
class L10nMgrExportHandler implements PostSaveInterface
{
    use AddFileToMatrix;

    /**
     * @param array $params
     * @return void
     */
    public function postExportAction(array $params)
    {
        if ($params['data']['exportType'] == 1) { //XML
            if ($params['data']['source_lang'] != $params['data']['translation_lang']) {
                if ($_REQUEST['export_xml_forcepreviewlanguage'] != $_REQUEST['SET']['lang']) {
                    $rootLine = join(
                        ',',
                        $this->getRootline(
                            $this->getSrcPid()
                        )
                    );
                    $where = 'AND ' . Constants::TABLE_LOCALIZER_L10NMGR_MM . '.uid_foreign = ' . (int)$params['data']['l10ncfg_id'] .
                        ' AND ' . Constants::TABLE_LOCALIZER_SETTINGS . '.pid IN (' . $rootLine . ')' .
                        BackendUtility::BEenableFields(Constants::TABLE_LOCALIZER_SETTINGS) . BackendUtility::deleteClause(Constants::TABLE_LOCALIZER_SETTINGS);
                    $resource = $this->getDatabaseConnection()->exec_SELECT_mm_query(
                        Constants::TABLE_LOCALIZER_SETTINGS . '.uid,' .
                        Constants::TABLE_LOCALIZER_SETTINGS . '.pid,' .
                        Constants::TABLE_LOCALIZER_SETTINGS . '.project_settings,
                            source_locale,target_locale',
                        Constants::TABLE_LOCALIZER_SETTINGS,
                        Constants::TABLE_LOCALIZER_L10NMGR_MM,
                        Constants::TABLE_L10NMGR_CONFIGURATION,
                        $where,
                        '',
                        Constants::TABLE_LOCALIZER_SETTINGS . '.pid IN (' . $rootLine . ')',
                        '0,1'
                    );
                    if ($resource) {
                        $row = $this->getDatabaseConnection()->sql_fetch_assoc($resource);

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