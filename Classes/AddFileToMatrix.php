<?php

declare(strict_types=1);

namespace Localizationteam\Localizer;

use Doctrine\DBAL\DBALException;
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
     * @throws DBALException
     */
    protected function addFileToMatrix(
        int $pid,
        int $localizerId,
        int $exportDataId,
        int $l10nConfigurationId,
        string $fileName,
        int $translationLanguage,
        int $action = 0
    ): void {
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_LANGUAGE_MM);
        $queryBuilder->getRestrictions()
            ->removeAll();
        $result = $queryBuilder
            ->select('uid_foreign', 'tablenames', 'ident', 'sorting')
            ->from(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('uid_local', $localizerId),
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->eq('ident', $queryBuilder->createNamedParameter('source')),
                        $queryBuilder->expr()->eq('uid_foreign', $isoCodeTargetLanguage)
                    ),
                    $queryBuilder->expr()->eq('source', $queryBuilder->createNamedParameter(Constants::TABLE_LOCALIZER_SETTINGS))
                )
            )
            ->executeQuery();
        $localizerLanguageRows = $this->fetchAllAssociative($result);
        if (count($localizerLanguageRows) > 0) {
            foreach ($localizerLanguageRows as $lRow) {
                $lRow['uid_local'] = $uid;
                $lRow['source'] = Constants::TABLE_EXPORTDATA_MM;
                GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
                    ->insert(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
                    ->values($lRow)
                    ->executeStatement();
            }
        }
    }

    /**
     * @return mixed
     */
    protected function getRootPath(int $uid)
    {
        return BackendUtility::getRecordPath($uid, '', 0);
    }

    protected function getLanguageIsoCode(int $sysLanguageId): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $result = $queryBuilder
            ->select('static_lang_isocode')
            ->from('sys_language')->where($queryBuilder->expr()->eq(
            'uid',
            $sysLanguageId
        ))->executeQuery();
        $row = $this->fetchAssociative($result);
        if (!empty($row)) {
            return $row['static_lang_isocode'];
        }
        return 0;
    }
}
