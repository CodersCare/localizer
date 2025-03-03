<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Handler;

use Doctrine\DBAL\DBALException;
use Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Model\Repository\LanguageRepository;
use Localizationteam\Localizer\Runner\SendFile;
use Localizationteam\Localizer\Traits\Data;
use Localizationteam\Localizer\Traits\Language;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * FileSender takes care to send file(s) to Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class FileSender extends AbstractHandler
{
    use Data;
    use Language;

    protected string $uploadPath = '';

    /**
     * @param $id
     * @throws Exception
     */
    public function init($id = 1): void
    {
        $this->initProcessId();
        if ($this->acquire()) {
            $this->initRun();
        }
        if ($this->canRun()) {
            $this->initData();
            $this->load();
        }
    }

    protected function acquire(): bool
    {
        $queryBuilder = self::getConnectionPool()
            ->getQueryBuilderForTable(Constants::TABLE_EXPORTDATA_MM);

        $affectedRows = $queryBuilder
            ->update(Constants::TABLE_EXPORTDATA_MM)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'status',
                        Constants::HANDLER_FILESENDER_START
                    ),
                    $queryBuilder->expr()->eq(
                        'action',
                        Constants::ACTION_SEND_FILE
                    ),
                    $queryBuilder->expr()->isNull(
                        'last_error'
                    ),
                    $queryBuilder->expr()->eq(
                        'processid',
                        $queryBuilder->createNamedParameter('')
                    )
                )
            )
            ->set('tstamp', time())
            ->set('processid', $this->processId)
            ->setMaxResults(Constants::HANDLER_FILESENDER_MAX_FILES)
            ->executeStatement();

        return $affectedRows > 0;
    }

    /**
     * @throws \TYPO3\CMS\Core\Exception
     */
    public function run(): void
    {
        if (!$this->canRun()) {
            return;
        }

        /** @var Typo3Version $typo3Version */
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);

        foreach ($this->data as $row) {
            if ((int)$row['action'] !== Constants::ACTION_SEND_FILE) {
                continue;
            }

            $file = $this->getFileAndPath($row['filename']);
            if ($file === false) {
                $this->addErrorResult(
                    $row['uid'],
                    Constants::STATUS_CART_ERROR,
                    $row['status'],
                    'File ' . $row['filename'] . ' not found'
                );
                continue;
            }

            $localizerSettings = $this->getLocalizerSettings($row['uid_local']);
            if (empty($localizerSettings)) {
                $this->addErrorResult(
                    $row['uid'],
                    Constants::STATUS_CART_ERROR,
                    $row['status'],
                    'LOCALIZER settings (' . $row['uid_local'] . ') not found'
                );
                continue;
            }

            $additionalConfiguration = [
                'uid' => $row['uid'],
                'localFile' => $file,
                'file' => $row['filename'],
            ];
            $deadline = $this->addDeadline($row);
            if (!empty($deadline)) {
                $additionalConfiguration['deadline'] = $deadline;
            }
            $metadata = $this->addMetaData($row);
            if (!empty($metadata)) {
                $additionalConfiguration['metadata'] = $metadata;
            }
            $translateAll = $this->translateAll($row);
            if ($translateAll === false) {
                if ($typo3Version->getMajorVersion() < 12) {
                    /** @var LanguageRepository $languageRepository */
                    $languageRepository = GeneralUtility::makeInstance(LanguageRepository::class, GeneralUtility::makeInstance(SiteFinder::class));
                    $targetLocalesUids = $languageRepository->getAllTargetLanguageUids(
                        $row['uid'],
                        Constants::TABLE_EXPORTDATA_MM
                    );
                    $additionalConfiguration['targetLocales'] =
                        $languageRepository->getStaticLanguagesCollateLocale($targetLocalesUids, true);
                } else {
                    $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($row['pid']);
                    $additionalConfiguration['targetLocales'][] = $site->getLanguageById($row['target_locale'])->getLocale()->__toString();
                }
            }

            $configuration = array_merge(
                $localizerSettings,
                $additionalConfiguration
            );

            /** @var SendFile $runner */
            $runner = GeneralUtility::makeInstance(SendFile::class);
            try {
                $runner->init($configuration);
            } catch (Exception $e) {
            }
            try {
                $runner->run();
            } catch (Exception $e) {
            }
            $response = $runner->getResponse();
            if (empty($response)) {
                $this->addSuccessResult(
                    $row['uid'],
                    Constants::STATUS_CART_FILE_SENT,
                    Constants::ACTION_REQUEST_STATUS
                );
            }
        }

        $this->result = $this->dispatchHandlerRunHasFinishedEvent($this->result);
    }

    /**
     * @param $fileName
     * @return false|string
     */
    protected function getFileAndPath($fileName)
    {
        $file = $this->getUploadPath() . $fileName;
        return file_exists($file) ? $file : false;
    }

    protected function getUploadPath(): string
    {
        if ($this->uploadPath === '') {
            $this->uploadPath = Environment::getPublicPath() . '/uploads/tx_l10nmgr/jobs/out/';
        }
        return $this->uploadPath;
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws DBALException
     * @throws \Doctrine\DBAL\Exception
     */
    protected function addDeadline(array $row): int
    {
        $deadline = 0;
        $queryBuilder = self::getConnectionPool()
            ->getQueryBuilderForTable(Constants::TABLE_EXPORTDATA_MM);
        $carts = $queryBuilder
            ->selectLiteral(
                'COALESCE (
                NULLIF(' . Constants::TABLE_EXPORTDATA_MM . '.deadline, 0), ' .
                Constants::TABLE_LOCALIZER_CART . '.deadline
            ) deadline'
            )
            ->from(Constants::TABLE_EXPORTDATA_MM)
            ->leftJoin(
                Constants::TABLE_EXPORTDATA_MM,
                Constants::TABLE_LOCALIZER_CART,
                Constants::TABLE_LOCALIZER_CART,
                $queryBuilder
                    ->expr()
                    ->eq(
                        Constants::TABLE_LOCALIZER_CART . '.uid_foreign',
                        $queryBuilder->quoteIdentifier(Constants::TABLE_EXPORTDATA_MM . '.uid_foreign'
                    )
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    Constants::TABLE_EXPORTDATA_MM . '.uid',
                    (int)$row['uid']
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        if (!empty($carts['deadline'])) {
            $deadline = (int)$carts['deadline'];
        }
        return $deadline;
    }

    protected function addMetaData(array &$row): array
    {
        $metaData = [];
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['localizer']['addMetaData'] ?? [];
        if (is_array($hooks)) {
            foreach ($hooks as $hookObj) {
                $metaData = GeneralUtility::callUserFunction($hookObj, $row, $this);
            }
        }
        return $metaData;
    }

    public function finish(int $time): void
    {
        $this->dataFinish($time);
    }
}
