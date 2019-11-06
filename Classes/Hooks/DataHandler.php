<?php

namespace Localizationteam\Localizer\Hooks;

use Exception;
use Localizationteam\Localizer\Api\ApiCalls;
use Localizationteam\Localizer\BackendUser;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Language;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * DataHandler
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 *
 */
class DataHandler
{
    use BackendUser, Language;

    /**
     * hook to post process TCA - Field Array
     * and to alter the configuration
     *
     * @param string $status
     * @param string $table
     * @param int $id
     * @param array $fieldArray
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $tceMain
     */
    public function processDatamap_postProcessFieldArray(
        $status,
        $table,
        $id,
        &$fieldArray,
        \TYPO3\CMS\Core\DataHandling\DataHandler &$tceMain
    ) {
        if ($table === Constants::TABLE_LOCALIZER_SETTINGS) {
            if ($this->isSaveAction()) {
                $currentRecord = $tceMain->recordInfo($table, $id, '*');
                if ($currentRecord === null) {
                    $currentRecord = [];
                }
                $checkArray = array_merge($currentRecord, $fieldArray);
                /** @var ApiCalls $localizerApi */
                $localizerApi = new ApiCalls(
                    $checkArray['type'],
                    $checkArray['url'],
                    $checkArray['workflow'],
                    $checkArray['projectkey'],
                    $checkArray['username'],
                    $checkArray['password'],
                    $checkArray['out_folder'],
                    $checkArray['in_folder']
                );
                try {
                    $valid = $localizerApi->areSettingsValid();
                    if ($valid === false) {
                        //should never arrive here as exception should occur!
                        $fieldArray['hidden'] = 1;
                    } else {
                        if ((int)$checkArray['type'] === 0) {
                            $fieldArray['project_settings'] = $localizerApi->getFolderInformation(true);
                        }
                        $fieldArray['last_error'] = '';
                        new FlashMessage('Localizer settings [' . $checkArray['title'] . '] successfully validated and saved',
                            'Success', 0);
                    }
                } catch (Exception $e) {
                    $fieldArray['last_error'] = $localizerApi->getLastError();
                    $fieldArray['hidden'] = 1;
                    new FlashMessage($e->getMessage());
                    new FlashMessage('Localizer settings [' . $checkArray['title'] . '] set to hidden', 'Error', 1);
                }
                if (method_exists($localizerApi, 'disconnect')) {
                    $localizerApi->disconnect();
                }
            }
        }
    }

    /**
     * @return bool
     */
    protected function isSaveAction()
    {
        return
            isset($_REQUEST['doSave']) && (bool)$_REQUEST['doSave'];
    }

    /**
     * @param array $incomingFieldArray
     * @param string $table
     * @param mixed $id
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $tceMain
     */
    public function processDatamap_preProcessFieldArray(
        array &$incomingFieldArray,
        $table,
        $id,
        \TYPO3\CMS\Core\DataHandling\DataHandler &$tceMain
    ) {
        if ($table === Constants::TABLE_EXPORTDATA_MM) {
            // if all languages are selected we skip other languages
            $targetLanguagesUidList = $this->getAllTargetUids($id);
            $targetLanguages = ',' . $incomingFieldArray['target_locale'] . ',';
            $allLocale = 0;
            if (strpos($targetLanguages, ',0,') !== false) {
                $incomingFieldArray['target_locale'] = join(',', $targetLanguagesUidList);
                $tceMain->datamap[$table][$id]['target_locale'] = $incomingFieldArray['target_locale'];
                $allLocale = 1;
            }
            if (isset($incomingFieldArray['all_locale'])) {
                if ((bool)$incomingFieldArray['all_locale'] === true) {
                    $incomingFieldArray['target_locale'] = join(',', $targetLanguagesUidList);
                    $tceMain->datamap[$table][$id]['target_locale'] = $incomingFieldArray['target_locale'];
                    $allLocale = 1;
                }
            }
            $incomingFieldArray['all_locale'] = $allLocale;
            $tceMain->datamap[$table]['id']['all_locale'] = $allLocale;
        }
    }

    /**
     * @param int $settingsId
     * @return array
     */
    protected function getAllTargetUids($settingsId)
    {
        $originalValues = BackendUtility::getRecord(Constants::TABLE_EXPORTDATA_MM, $settingsId);
        return $this->getAllTargetLanguageUids($originalValues['uid_local'], Constants::TABLE_LOCALIZER_SETTINGS);
    }

    /**
     * Hook for displaying small icon in page tree, web>List and page module.
     *
     * @param $p
     * @param $pObj
     *
     * @return string [type]...
     */
    function recStatInfo($p, $pObj)
    {
        if (!empty($this->getBackendUser()->groupData['allowed_languages']) || $this->getBackendUser()->isAdmin()) {
            return $this->calcStat($p,
                $this->getDatabaseConnection()->cleanIntList($this->getBackendUser()->groupData['allowed_languages']));
        } else {
            return '';
        }
    }

    function calcStat($p, $languageList, $noLink = false)
    {
        $output = '';

        if ($p[0] != 'pages') {
            $records = $this->getDatabaseConnection()->exec_SELECTgetRows('*', 'tx_l10nmgr_index',
                'tablename=' . $this->getDatabaseConnection()->fullQuoteStr($p[0],
                    'tx_l10nmgr_index') . ' AND recuid=' . (int)$p[1] . ' AND (translation_lang IN (' . $languageList . ') OR ' . $languageList . ' = 0)' . ' AND workspace=' . (int)$this->getBackendUser()->workspace);
        } else {
            $records = $this->getDatabaseConnection()->exec_SELECTgetRows('*', 'tx_l10nmgr_index',
                'recpid=' . (int)$p[1] . ' AND (translation_lang IN (' . $languageList . ') OR ' . $languageList . ' = 0)' . ' AND workspace=' . (int)$this->getBackendUser()->workspace);
        }
        $flags = [];
        foreach ($records as $r) {
            $flags['new'] += $r['flag_new'];
            $flags['unknown'] += $r['flag_unknown'];
            $flags['update'] += $r['flag_update'];
            $flags['noChange'] += $r['flag_noChange'];
        }
        if (count($records)) {
            // Setting icon:
            $msg = '';
            if ($flags['new'] && !$flags['unknown'] && !$flags['noChange'] && !$flags['update']) {
                $msg .= 'None of ' . $flags['new'] . ' elements are translated.';
                $output = '<img src="../' . ExtensionManagementUtility::siteRelPath('l10nmgr') . 'Resources/Public/Images/flags_new.png" hspace="2" width="10" height="16" alt="' . htmlspecialchars($msg) . '" title="' . htmlspecialchars($msg) . '" />';
            } elseif ($flags['new'] || $flags['update']) {
                if ($flags['update']) {
                    $msg .= $flags['update'] . ' elements to update. ';
                }
                if ($flags['new']) {
                    $msg .= $flags['new'] . ' new elements found. ';
                }
                $output = '<img src="../' . ExtensionManagementUtility::siteRelPath('l10nmgr') . 'Resources/Public/Images/flags_update.png" hspace="2" width="10" height="16" alt="' . htmlspecialchars($msg) . '" title="' . htmlspecialchars($msg) . '" />';
            } elseif ($flags['unknown']) {
                $msg .= 'Translation status is unknown for ' . $flags['unknown'] . ' elements. Please check and update. ';
                $output = '<img src="../' . ExtensionManagementUtility::siteRelPath('l10nmgr') . 'Resources/Public/Images/flags_unknown.png" hspace="2" width="10" height="16" alt="' . htmlspecialchars($msg) . '" title="' . htmlspecialchars($msg) . '" />';
            } elseif ($flags['noChange']) {
                $msg .= 'All ' . $flags['noChange'] . ' translations OK';
                $output = '<img src="../' . ExtensionManagementUtility::siteRelPath('l10nmgr') . 'Resources/Public/Images/flags_ok.png" hspace="2" width="10" height="16" alt="' . htmlspecialchars($msg) . '" title="' . htmlspecialchars($msg) . '" />';
            } else {
                $msg .= 'Nothing to do. ';
                $msg .= '[n/?/u/ok=' . implode('/', $flags) . ']';
                $output = '<img src="../' . ExtensionManagementUtility::siteRelPath('l10nmgr') . 'Resources/Public/Images/flags_none.png" hspace="2" width="10" height="16" alt="' . htmlspecialchars($msg) . '" title="' . htmlspecialchars($msg) . '" />';
            }
            $output = !$noLink ? '<a href="#" onclick="' . htmlspecialchars('parent.list_frame.location.href="' . $GLOBALS['BACK_PATH'] . ExtensionManagementUtility::siteRelPath('l10nmgr') . 'cm2/index.php?table=' . $p[0] . '&uid=' . $p[1] . '&languageList=' . rawurlencode($languageList) . '"; return false;') . '" target="listframe">' . $output . '</a>' : $output;
        }
        return $output;
    }

    /**
     * @return DatabaseConnection
     */
    public function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}