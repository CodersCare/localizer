<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Hooks;

use Doctrine\DBAL\DBALException;
use Exception;
use Localizationteam\Localizer\Api\ApiCalls;
use Localizationteam\Localizer\BackendUser;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Data;
use Localizationteam\Localizer\Language;
use Localizationteam\Localizer\Model\Repository\LanguageRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * DataHandler
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class DataHandlerHook
{
    use BackendUser;
    use Language;
    use Data;

    /**
     * hook to post process TCA - Field Array
     * and to alter the configuration
     *
     * @param string $status
     * @param string $table
     * @param string|int $id
     * @param array $fieldArray
     * @param DataHandler $tceMain
     */
    public function processDatamap_postProcessFieldArray(
        string $status,
        string $table,
        $id,
        array &$fieldArray,
        DataHandler $tceMain
    ): void {
        if ($table === Constants::TABLE_LOCALIZER_SETTINGS) {
            if ($this->isSaveAction()) {
                $currentRecord = $tceMain->recordInfo($table, $id, '*');
                if ($currentRecord === null) {
                    $currentRecord = [];
                }
                $checkArray = array_merge($currentRecord, $fieldArray);
                if ($checkArray['type'] === 0 || $checkArray['type'] === '0') {
                    $localizerApi = new ApiCalls(
                        (string)$checkArray['type'],
                        (string)$checkArray['url'],
                        (string)$checkArray['workflow'],
                        (string)$checkArray['projectkey'],
                        (string)$checkArray['username'],
                        (string)$checkArray['password'],
                        (string)$checkArray['out_folder'],
                        (string)$checkArray['in_folder']
                    );
                    try {
                        $valid = $localizerApi->areSettingsValid();
                        if ($valid === false) {
                            //should never arrive here as exception should occur!
                            $fieldArray['hidden'] = 1;
                        } else {
                            $fieldArray['project_settings'] = $localizerApi->getFolderInformation(true);
                            $fieldArray['last_error'] = null;
                            new FlashMessage(
                                'Localizer settings [' . $checkArray['title'] . '] successfully validated and saved',
                                'Success',
                                0
                            );
                        }
                    } catch (Exception $e) {
                        $fieldArray['last_error'] = $localizerApi->getLastError();
                        $fieldArray['hidden'] = 1;
                        new FlashMessage($e->getMessage());
                        new FlashMessage('Localizer settings [' . $checkArray['title'] . '] set to hidden', 'Error', 1);
                    }
                }
            }
        }
    }

    /**
     * @return bool
     */
    protected function isSaveAction(): bool
    {
        return
            isset($_REQUEST['doSave']) && $_REQUEST['doSave'];
    }

    /**
     * @param array $incomingFieldArray
     * @param string $table
     * @param string $id
     * @param DataHandler $tceMain
     */
    public function processDatamap_preProcessFieldArray(
        array &$incomingFieldArray,
        string $table,
        string $id,
        DataHandler $tceMain
    ): void {
        if ($table === Constants::TABLE_EXPORTDATA_MM && isset($incomingFieldArray['target_locale'])) {
            // if all languages are selected we skip other languages
            $targetLanguagesUidList = $this->getAllTargetUids($id);
            $targetLanguages = ',' . $incomingFieldArray['target_locale'] . ',';
            $allLocale = 0;
            if (strpos($targetLanguages, ',0,') !== false) {
                $incomingFieldArray['target_locale'] = implode(',', $targetLanguagesUidList);
                $tceMain->datamap[$table][$id]['target_locale'] = $incomingFieldArray['target_locale'];
                $allLocale = 1;
            }
            if (isset($incomingFieldArray['all_locale']) && (bool)$incomingFieldArray['all_locale'] === true) {
                $incomingFieldArray['target_locale'] = implode(',', $targetLanguagesUidList);
                $tceMain->datamap[$table][$id]['target_locale'] = $incomingFieldArray['target_locale'];
                $allLocale = 1;
            }
            $incomingFieldArray['all_locale'] = $allLocale;
            $tceMain->datamap[$table]['id']['all_locale'] = $allLocale;
        }
    }

    /**
     * @param string $settingsId
     * @return array
     */
    protected function getAllTargetUids(string $settingsId): array
    {
        $originalValues = BackendUtility::getRecord(Constants::TABLE_EXPORTDATA_MM, (int)$settingsId);
        $languageRepository = GeneralUtility::makeInstance(LanguageRepository::class);
        return $languageRepository->getAllTargetLanguageUids($originalValues['uid_local'], Constants::TABLE_LOCALIZER_SETTINGS);
    }

    /**
     * Hook for displaying small icon in page tree, web>List and page module.
     *
     * @param array $p
     * @param mixed $pObj
     *
     * @return string [type]...
     * @throws DBALException
     */
    public function recStatInfo(array $p, $pObj): string
    {
        if (!empty($this->getBackendUser()->groupData['allowed_languages']) || $this->getBackendUser()->isAdmin()) {
            return $this->calcStat(
                $p,
                implode(',', GeneralUtility::intExplode(',', $this->getBackendUser()->groupData['allowed_languages']))
            );
        }
        return '';
    }

    /**
     * @param $p
     * @param $languageList
     * @param bool $noLink
     * @return string
     * @throws DBALException
     */
    public function calcStat($p, $languageList, bool $noLink = false): string
    {
        $output = '';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            'tx_l10nmgr_index'
        );
        $queryBuilder->getRestrictions()
            ->removeAll();
        if ($languageList === 0) {
            $noLanguage = '0';
            $languageValues = [];
        } else {
            $noLanguage = '1';
            $languageValues = GeneralUtility::intExplode(',', $languageList, true);
        }
        if ($p[0] != 'pages') {
            $result = $queryBuilder
                ->select('*')
                ->from('tx_l10nmgr_index')
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            'tablename',
                            $queryBuilder->createNamedParameter($p[0])
                        ),
                        $queryBuilder->expr()->eq(
                            'recuid',
                            (int)$p[1]
                        ),
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->in(
                                'translation_lang',
                                $languageValues
                            ),
                            $queryBuilder->expr()->eq(
                                '0',
                                $noLanguage
                            )
                        ),
                        $queryBuilder->expr()->eq(
                            'workspaces',
                            $this->getBackendUser()->workspace
                        )
                    )
                )
                ->execute();
        } else {
            $result = $queryBuilder
                ->select('*')
                ->from('tx_l10nmgr_index')
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            'tablename',
                            $queryBuilder->createNamedParameter($p[0])
                        ),
                        $queryBuilder->expr()->eq(
                            'recpid',
                            (int)$p[1]
                        ),
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->in(
                                'translation_lang',
                                $languageValues
                            ),
                            $queryBuilder->expr()->eq(
                                '0',
                                $noLanguage
                            )
                        ),
                        $queryBuilder->expr()->eq(
                            'workspaces',
                            $this->getBackendUser()->workspace
                        )
                    )
                )
                ->execute();
        }
        $records = $this->fetchAllAssociative($result);
        $flags = [];
        if (is_array($records)) {
            foreach ($records as $r) {
                $flags['new'] += $r['flag_new'];
                $flags['unknown'] += $r['flag_unknown'];
                $flags['update'] += $r['flag_update'];
                $flags['noChange'] += $r['flag_noChange'];
            }
            // Setting icon:
            $msg = '';
            $extPath = PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath('l10nmgr'));
            if ($flags['new'] && !$flags['unknown'] && !$flags['noChange'] && !$flags['update']) {
                $msg .= 'None of ' . $flags['new'] . ' elements are translated.';
                $output = sprintf(
                    '<img src="../%sResources/Public/Images/flags_new.png" hspace="2" width="10" height="16" alt="%s" title="%s" />',
                    $extPath,
                    htmlspecialchars($msg),
                    htmlspecialchars($msg)
                );
            } elseif ($flags['new'] || $flags['update']) {
                if ($flags['update']) {
                    $msg .= $flags['update'] . ' elements to update. ';
                }
                if ($flags['new']) {
                    $msg .= $flags['new'] . ' new elements found. ';
                }
                $output = sprintf(
                    '<img src="../%sResources/Public/Images/flags_update.png" hspace="2" width="10" height="16" alt="%s" title="%s" />',
                    $extPath,
                    htmlspecialchars($msg),
                    htmlspecialchars($msg)
                );
            } elseif ($flags['unknown']) {
                $msg .= 'Translation status is unknown for ' . $flags['unknown'] . ' elements. Please check and update. ';
                $output = sprintf(
                    '<img src="../%sResources/Public/Images/flags_unknown.png" hspace="2" width="10" height="16" alt="%s" title="%s" />',
                    $extPath,
                    htmlspecialchars($msg),
                    htmlspecialchars($msg)
                );
            } elseif ($flags['noChange']) {
                $msg .= 'All ' . $flags['noChange'] . ' translations OK';
                $output = sprintf(
                    '<img src="../%sResources/Public/Images/flags_ok.png" hspace="2" width="10" height="16" alt="%s" title="%s" />',
                    $extPath,
                    htmlspecialchars($msg),
                    htmlspecialchars($msg)
                );
            } else {
                $msg .= 'Nothing to do. ';
                $msg .= '[n/?/u/ok=' . implode('/', $flags) . ']';
                $output = sprintf(
                    '<img src="../%sResources/Public/Images/flags_none.png" hspace="2" width="10" height="16" alt="%s" title="%s" />',
                    $extPath,
                    htmlspecialchars($msg),
                    htmlspecialchars($msg)
                );
            }
            if (!$noLink) {
                $output = sprintf(
                    '<a href="#" onclick="%s" target="listframe">%s</a>',
                    htmlspecialchars('parent.list_frame.location.href="' . $GLOBALS['BACK_PATH'] . $extPath . 'cm2/index.php?table=' . $p[0] . '&uid=' . $p[1] . '&languageList=' . rawurlencode((string)$languageList) . '"; return false;'),
                    $output
                );
            }
        }
        return $output;
    }
}
