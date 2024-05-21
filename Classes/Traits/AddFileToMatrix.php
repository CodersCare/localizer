<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Traits;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Localizationteam\Localizer\Constants;
use PDO;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * AddFileToMatrix
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
trait AddFileToMatrix
{
    use BackendUserTrait;
    use ConnectionPoolTrait;

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
        int $sourceLanguage,
        int $action = 0
    ): void {
        $time = time();
        $databaseConnection = self::getConnectionPool()->getConnectionForTable(
            Constants::TABLE_EXPORTDATA_MM
        );
        $databaseConnection
            ->insert(
                Constants::TABLE_EXPORTDATA_MM,
                [
                    'pid' => $pid,
                    'crdate' => $time,
                    'cruser_id' => $this->getBackendUser()->getUserId(),
                    'status' => Constants::STATUS_CART_FILE_EXPORTED,
                    'action' => $action,
                    'uid_local' => $localizerId,
                    'uid_export' => $exportDataId,
                    'uid_foreign' => $l10nConfigurationId,
                    'localizer_path' => $this->getRootPath($pid),
                    'filename' => $fileName,
                    'source_locale' => 1,
                    'target_locale' => 1,
                    'source_language' => $sourceLanguage,
                    'target_language' => $translationLanguage,
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
                    PDO::PARAM_INT,
                    PDO::PARAM_INT,
                ]
            );

        $uid = $databaseConnection->lastInsertId(Constants::TABLE_EXPORTDATA_MM);

        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        if ($typo3Version->getMajorVersion() < 12) {
            $isoCodeTargetLanguage = $this->getLanguageIsoCode($translationLanguage);
            $queryBuilder = self::getConnectionPool()
                ->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_LANGUAGE_MM);
            $queryBuilder->getRestrictions()->removeAll();
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
                    self::getConnectionPool()
                        ->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
                        ->insert(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
                        ->values($lRow)
                        ->executeStatement();
                }
            }
        } else {

        }

    }

    /**
     * @return mixed
     */
    protected function getRootPath(int $uid)
    {
        return BackendUtility::getRecordPath($uid, '', 0);
    }

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws DBALException
     */
    protected function getLanguageIsoCode(int $sysLanguageId): int
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_SYS_LANGUAGE);
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->select('static_lang_isocode')
            ->from(Constants::TABLE_SYS_LANGUAGE)
            ->where(
                $queryBuilder->expr()->eq('uid', $sysLanguageId)
            )
            ->executeQuery()
            ->fetchAssociative();

        if (!empty($row)) {
            return $row['static_lang_isocode'];
        }

        return 0;
    }
}
