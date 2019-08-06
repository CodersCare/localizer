<?php

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Data;
use Localizationteam\Localizer\File;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * FileImporter $COMMENT$
 *
 * @author      Peter Russ<peter.russ@4many.net>
 * @package     TYPO3
 * @date        20150924-1112
 * @subpackage  localizer
 *
 */
class FileImporter extends AbstractHandler
{
    use Data, File;

    /**
     * @param $id
     * @throws Exception
     */
    public function init($id = 1)
    {
        $where = 'deleted = 0 AND hidden = 0 AND status = ' . Constants::HANDLER_FILEIMPORTER_START .
            ' AND action = ' . Constants::ACTION_IMPORT_FILE .
            ' AND last_error = "" AND processid = ""' .
            ' LIMIT ' . Constants::HANDLER_FILEIMPORTER_MAX_FILES;
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
                            $response = $this->processImport($row['filename'], $originalResponse['files']);
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

    /**
     * @param string $originalFileName
     * @param array $files
     * @return array
     * @throws FolderDoesNotExistException
     */
    protected function processImport($originalFileName, array $files)
    {
        $response = [];
        foreach ($files as $fileStatus) {
            $introductionXmlPath = PATH_site . 'uploads/tx_l10nmgr/jobs/in/instruction.xml';
            if (file_exists($introductionXmlPath)) {
                unlink($introductionXmlPath);
            }
            $fileNameAndPath = $this->getLocalFilename($originalFileName, $fileStatus['locale']);
            $context = GeneralUtility::getApplicationContext()->__toString();
            $action = ($context ? ('TYPO3_CONTEXT=' . $context . ' ') : '') .
                PATH_site . 'typo3/cli_dispatch.phpsh l10nmgr_import --task=importFile --file=' .
                $fileNameAndPath;
            $response[] = [
                'http_status_code' => 200,
                'response'         => [
                    'action' => exec($action),
                    'file'   => $originalFileName,
                    'locale' => $fileStatus['locale'],
                ],
            ];
        }
        return $response;
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
                Constants::STATUS_CART_FILE_IMPORTED,
                0
            );
        } else {
            $this->addErrorResult(
                $uid,
                Constants::STATUS_CART_ERROR,
                0,
                'Error while importing File'
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