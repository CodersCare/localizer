<?php

namespace Localizationteam\Localizer;

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * AddFileToMatrix
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 *
 */
trait AddFileToMatrix
{
    use BackendUser, DatabaseConnection;

    /**
     * @param int $pid
     * @param int $localizerId
     * @param int $exportDataId
     * @param int $l10nConfigurationId
     * @param string $fileName
     * @param int $translationLanguage
     * @param int $action
     */
    protected function addFileToMatrix(
        $pid,
        $localizerId,
        $exportDataId,
        $l10nConfigurationId,
        $fileName,
        $translationLanguage,
        $action = 0
    ) {
        $time = time();
        $fields = [
            'pid' => (int)$pid,
            'crdate' => $time,
            'cruser_id' => $this->getBackendUser()->user['uid'],
            'status' => Constants::STATUS_CART_FILE_EXPORTED,
            'action' => (int)$action,
            'uid_local' => (int)$localizerId,
            'uid_export' => (int)$exportDataId,
            'uid_foreign' => (int)$l10nConfigurationId,
            'localizer_path' => $this->getRootPath((int)$pid),
            'filename' => (string)$fileName,
            'source_locale' => 1,
            'target_locale' => 1,
        ];
        $this->getDatabaseConnection()
            ->exec_INSERTquery(
                Constants::TABLE_EXPORTDATA_MM,
                $fields
            );
        $uid = $this->getDatabaseConnection()->sql_insert_id();
        $isoCodeTargetLanguage = $this->getLanguageIsoCode($translationLanguage);
        $localizerLanguageRows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid_foreign,tablenames,ident,sorting',
            Constants::TABLE_LOCALIZER_LANGUAGE_MM,
            'uid_local = ' . (int)$localizerId .
            ' AND ( ident = "source" OR uid_foreign = ' . (int)$isoCodeTargetLanguage . ')' .
            ' AND source = "' . Constants::TABLE_LOCALIZER_SETTINGS . '"'
        );
        if (count($localizerLanguageRows) > 0) {
            foreach ($localizerLanguageRows as $lRow) {
                $lRow['uid_local'] = $uid;
                $lRow['source'] = Constants::TABLE_EXPORTDATA_MM;
                $this->getDatabaseConnection()->exec_INSERTquery(
                    Constants::TABLE_LOCALIZER_LANGUAGE_MM,
                    $lRow
                );
            }
        }
    }

    /**
     * @param int $uid
     * @return mixed
     */
    protected function getRootPath($uid)
    {
        return BackendUtility::getRecordPath($uid, '', 0);
    }

    /**
     * @param int $sysLanguageId
     * @return int
     */
    protected function getLanguageIsoCode($sysLanguageId)
    {
        $row = $this->getDatabaseConnection()
            ->exec_SELECTgetSingleRow('static_lang_isocode', 'sys_language', 'uid=' . (int)$sysLanguageId);

        return $row['static_lang_isocode'];
    }
}