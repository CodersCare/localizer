<?php

namespace Localizationteam\Localizer;

use PDO;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * AddFileToMatrix
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
trait AddFileToMatrix
{
    use BackendUser;

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
        int $pid,
        int $localizerId,
        int $exportDataId,
        int $l10nConfigurationId,
        string $fileName,
        int $translationLanguage,
        int $action = 0
    ) {
        $time = time();
        $databaseConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(
            Constants::TABLE_EXPORTDATA_MM
        );
        $databaseConnection
            ->insert(
                Constants::TABLE_EXPORTDATA_MM,
                [
                    'pid' => $pid,
                    'crdate' => $time,
                    'cruser_id' => $this->getBackendUser()->user['uid'],
                    'status' => Constants::STATUS_CART_FILE_EXPORTED,
                    'action' => $action,
                    'uid_local' => $localizerId,
                    'uid_export' => $exportDataId,
                    'uid_foreign' => $l10nConfigurationId,
                    'localizer_path' => $this->getRootPath($pid),
                    'filename' => $fileName,
                    'source_locale' => 1,
                    'target_locale' => 1,
                ],
                [
                    PDO::PARAM_INT,
                    PDO::PARAM_INT,
                    PDO::PARAM_INT,
                    PDO::PARAM_INT,
                    PDO::PARAM_INT,
                    PDO::PARAM_INT,
                    PDO::PARAM_INT,
                    PDO::PARAM_INT,
                    PDO::PARAM_INT,
                    PDO::PARAM_STR,
                    PDO::PARAM_INT,
                    PDO::PARAM_INT,
                ]
            );

        $uid = $databaseConnection->lastInsertId(Constants::TABLE_EXPORTDATA_MM);
        $isoCodeTargetLanguage = $this->getLanguageIsoCode($translationLanguage);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_LOCALIZER_LANGUAGE_MM
        );
        $queryBuilder->getRestrictions()
            ->removeAll();
        $localizerLanguageRows = $queryBuilder
            ->select('uid_foreign', 'tablenames', 'ident', 'sorting')
            ->from(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $localizerId
                    ),
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->eq(
                            'ident',
                            $queryBuilder->createNamedParameter('source', PDO::PARAM_STR)
                        ),
                        $queryBuilder->expr()->eq(
                            'uid_foreign',
                            $isoCodeTargetLanguage
                        )
                    ),
                    $queryBuilder->expr()->eq(
                        'source',
                        $queryBuilder->createNamedParameter(Constants::TABLE_LOCALIZER_SETTINGS, PDO::PARAM_STR)
                    )
                )
            )
            ->execute()
            ->fetchAllAssociative();
        if (count($localizerLanguageRows) > 0) {
            foreach ($localizerLanguageRows as $lRow) {
                $lRow['uid_local'] = $uid;
                $lRow['source'] = Constants::TABLE_EXPORTDATA_MM;
                GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
                    ->insert(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
                    ->values(
                        $lRow
                    )
                    ->execute();
            }
        }
    }

    /**
     * @param int $uid
     * @return mixed
     */
    protected function getRootPath(int $uid)
    {
        return BackendUtility::getRecordPath($uid, '', 0);
    }

    /**
     * @param int $sysLanguageId
     * @return int
     */
    protected function getLanguageIsoCode(int $sysLanguageId): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $row = $queryBuilder
            ->select('static_lang_isocode')
            ->from('sys_language')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    (int)$sysLanguageId
                )
            )
            ->execute()
            ->fetchAssociative();
        if (!empty($row)) {
            return $row['static_lang_isocode'];
        }
        return 0;
    }
}
