<?php

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Data;
use Localizationteam\Localizer\File;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * FileImporter $COMMENT$
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
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
        parent::init($id);
        if ($this->canRun()) {
            $this->initData();
            $this->load();
        }
    }

    /**
     * @return bool
     */
    protected function acquire()
    {
        $acquired = false;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_EXPORTDATA_MM);
        $queryBuilder->getRestrictions();
        $affectedRows = $queryBuilder
            ->update(Constants::TABLE_EXPORTDATA_MM)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'status',
                        $queryBuilder->createNamedParameter(Constants::HANDLER_FILEIMPORTER_START, PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'action',
                        $queryBuilder->createNamedParameter(Constants::ACTION_IMPORT_FILE, PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'last_error',
                        $queryBuilder->createNamedParameter('', PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'processid',
                        $queryBuilder->createNamedParameter('', PDO::PARAM_STR)
                    )
                )
            )
            ->set('tstamp', time())
            ->set('processid', $this->processId)
            ->setMaxResults(Constants::HANDLER_FILEIMPORTER_MAX_FILES)
            ->execute();
        if ($affectedRows > 0) {
            $acquired = true;
        }
        return $acquired;
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
                PATH_site . 'typo3/sysext/core/bin/typo3 l10nmanager:import -t importFile --file ' .
                $fileNameAndPath;
            $response[] = [
                'http_status_code' => 200,
                'response' => [
                    'action' => exec($action . ' 2>&1'),
                    'file' => $originalFileName,
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