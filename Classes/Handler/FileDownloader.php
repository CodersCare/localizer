<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Handler;

use Doctrine\DBAL\DBALException;
use Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Runner\DownloadFile;
use Localizationteam\Localizer\Traits\Data;
use Localizationteam\Localizer\Traits\File;
use Localizationteam\Localizer\Traits\Language;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * FileDownloader
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class FileDownloader extends AbstractHandler
{
    use Data;
    use File;
    use Language;

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

    /**
     * @throws DBALException
     */
    protected function acquire(): bool
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(
            Constants::TABLE_EXPORTDATA_MM
        );
        $affectedRows = $queryBuilder
            ->update(Constants::TABLE_EXPORTDATA_MM)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'status',
                        Constants::HANDLER_FILEDOWNLOADER_START
                    ),
                    $queryBuilder->expr()->eq(
                        'action',
                        Constants::ACTION_DOWNLOAD_FILE
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
            ->setMaxResults(Constants::HANDLER_FILEDOWNLOADER_MAX_FILES)
            ->executeStatement();

        return $affectedRows > 0;
    }

    /**
     * @throws \TYPO3\CMS\Core\Exception
     * @throws Exception
     */
    public function run(): void
    {
        if (!$this->canRun()) {
            return;
        }

        foreach ($this->data as $row) {
            $localizerSettings = $this->getLocalizerSettings($row['uid_local']);
            if (empty($localizerSettings)) {
                $this->addErrorResult(
                    $row['uid'],
                    Constants::STATUS_CART_ERROR,
                    $row['status'],
                    'LOCALIZER settings (' . $row['uid_local'] . ') not found'
                );
            } elseif ($row['response'] !== '') {
                $originalResponse = json_decode($row['response'], true);
                if ($originalResponse === null) {
                    $this->addErrorResult(
                        $row['uid'],
                        Constants::STATUS_CART_ERROR,
                        Constants::HANDLER_FILEDOWNLOADER_ERROR_STATUS_RESET,
                        'Expected array but could not decode response. Must get status from Localizer',
                        Constants::HANDLER_FILEDOWNLOADER_ERROR_ACTION_RESET
                    );
                } elseif (isset($originalResponse['files'])) {
                    $response = $this->processDownload(
                        $localizerSettings,
                        $row['filename'],
                        $originalResponse['files'],
                        $row
                    );
                    $this->processResponse($row['uid'], $response);
                } else {
                    $this->addErrorResult(
                        $row['uid'],
                        Constants::STATUS_CART_ERROR,
                        Constants::HANDLER_FILEDOWNLOADER_ERROR_STATUS_RESET,
                        'No information about files found in response. Must get status from Localizer',
                        Constants::HANDLER_FILEDOWNLOADER_ERROR_ACTION_RESET
                    );
                }
            } else {
                $this->addErrorResult(
                    $row['uid'],
                    Constants::STATUS_CART_ERROR,
                    Constants::HANDLER_FILEDOWNLOADER_ERROR_STATUS_RESET,
                    'No Localizer response found. Must get status from Localizer',
                    Constants::HANDLER_FILEDOWNLOADER_ERROR_ACTION_RESET
                );
            }
        }

        $this->result = $this->dispatchHandlerRunHasFinishedEvent($this->result);
    }

    /**
     * @throws Exception
     */
    protected function processDownload(
        array $localizerSettings,
        string $originalFileName,
        array $files,
        array $row
    ): array {
        $processFiles = [];
        $iso2 = $this->getIso2ForLocale($row);
        foreach ($files as $fileStatus) {
            if ($fileStatus['status'] === Constants::API_TRANSLATION_STATUS_TRANSLATED) {
                $processFiles['processFiles'][] = [
                    'locale' => $iso2,
                    'local' => $this->getLocalFilename($originalFileName, $iso2),
                    'hotfolder' => $this->getRemoteFilename($fileStatus['file'], ''),
                    'remote' => $this->getRemoteFilename($fileStatus['file'], $iso2),
                    'remoteFilename' => $fileStatus['file'],
                ];
            }
        }
        $configuration = array_merge(
            $localizerSettings,
            $processFiles
        );
        /** @var DownloadFile $runner */
        $runner = GeneralUtility::makeInstance(DownloadFile::class);
        $runner->init($configuration);
        $runner->run($configuration);
        return $runner->getResponse();
    }

    protected function getRemoteFilename(string $fileName, string $locale): string
    {
        return $locale . '\\' . $fileName;
    }

    protected function processResponse(int $uid, array $responses)
    {
        $success = true;
        foreach ($responses as $response) {
            if (!is_array($response)) {
                $success = false;
                continue;
            }
            if ($response['http_status_code'] && $response['http_status_code'] > 399) {
                DebugUtility::debug($response, __METHOD__ . ':' . __LINE__);
                $success = false;
            }
        }
        if ($success === true) {
            $this->addSuccessResult(
                $uid,
                Constants::STATUS_CART_FILE_DOWNLOADED
            );
        } else {
            $this->addErrorResult(
                $uid,
                Constants::STATUS_CART_ERROR,
                Constants::HANDLER_FILEDOWNLOADER_ERROR_STATUS_RESET,
                'Error while downloading from Localizer',
                Constants::HANDLER_FILEDOWNLOADER_ERROR_ACTION_RESET
            );
        }
    }

    public function finish(int $time): void
    {
        $this->dataFinish($time);
    }
}
