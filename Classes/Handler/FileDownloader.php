<?php

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Data;
use Localizationteam\Localizer\File;
use Localizationteam\Localizer\Language;
use Localizationteam\Localizer\Runner\DownloadFile;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * FileDownloader $COMMENT$
 *
 * @author      Peter Russ<peter.russ@4many.net>
 * @package     TYPO3
 * @date        20150920-1314
 * @subpackage  localizer
 *
 */
class FileDownloader extends AbstractHandler
{
    use Data, File, Language;

    /**
     * @param $id
     * @throws Exception
     */
    public function init($id = 1)
    {
        $where = 'deleted = 0 AND hidden = 0 AND status = ' . Constants::HANDLER_FILEDOWNLOADER_START .
            ' AND action = ' . Constants::ACTION_DOWNLOAD_FILE .
            ' AND last_error = "" AND processid = ""' .
            ' LIMIT ' . Constants::HANDLER_FILEDOWNLOADER_MAX_FILES;

        $this->setAcquireWhere($where);
        parent::init($id);
        if ($this->canRun()) {
            $this->initData();
            $this->load();
        }
    }

    function run()
    {
        if ($this->canRun() === true) {
            foreach ($this->data as $row) {
                $localizerSettings = $this->getLocalizerSettings($row['uid_local']);
                if ($localizerSettings === false) {
                    $this->addErrorResult(
                        $row['uid'],
                        Constants::STATUS_CART_ERROR,
                        $row['status'],
                        'LOCALIZER settings (' . $row['uid_local'] . ') not found'
                    );
                } else {
                    if ($row['response'] !== '') {
                        $originalResponse = json_decode($row['response'], true);
                        if ($originalResponse === null) {
                            $this->addErrorResult(
                                $row['uid'],
                                Constants::STATUS_CART_ERROR,
                                Constants::HANDLER_FILEDOWNLOADER_ERROR_STATUS_RESET,
                                'Expected array but could not decode response. Must get status from Localizer',
                                Constants::HANDLER_FILEDOWNLOADER_ERROR_ACTION_RESET
                            );
                        } else {
                            if (isset($originalResponse['files'])) {
                                $response = $this->processDownload($localizerSettings, $row['filename'],
                                    $originalResponse['files']);
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
            }
        }
    }

    /**
     * @param array $localizerSettings
     * @param string $originalFileName
     * @param array $files
     * @return array
     */
    protected function processDownload(array $localizerSettings, $originalFileName, array $files)
    {
        $processFiles = [];
        foreach ($files as $fileStatus) {
            if ($fileStatus['status'] === Constants::API_TRANSLATION_STATUS_TRANSLATED) {
                $processFiles['processFiles'][] = [
                    'locale'    => $fileStatus['locale'],
                    'local'     => $this->getLocalFilename($originalFileName, $fileStatus['locale']),
                    'hotfolder' => $this->getRemoteFilename($fileStatus['file'], ''),
                    'remote'    => $this->getRemoteFilename($fileStatus['file'],
                        $this->getIso2ForLocale($fileStatus['locale'])),
                ];
            } else {
                //fixme:errorhandling
            }
        }
        $configuration = array_merge(
            $localizerSettings,
            $processFiles
        );
        /** @var DownloadFile $runner */
        $runner = GeneralUtility::makeInstance(DownloadFile::class);
        $runner->init($configuration);
        $runner->run();
        $response = $runner->getResponse();
        return json_decode($response, true);
    }

    /**
     * @param string $fileName
     * @param string $locale
     * @return string
     */
    protected function getRemoteFilename($fileName, $locale)
    {
        return $locale . '\\' . $fileName;
    }

    /**
     * @param int $uid
     * @param array $responses
     */
    protected function processResponse($uid, $responses)
    {
        $success = true;
        foreach ($responses as $response) {
            if ($response['http_status_code'] > 399) {
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

    /**
     * @param int $time
     * @return void
     */
    function finish($time)
    {
        $this->dataFinish($time);
    }
}