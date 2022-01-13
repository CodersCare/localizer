<?php

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Data;
use Localizationteam\Localizer\File;
use PDO;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * FileImporter $COMMENT$
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class FileImporter extends AbstractHandler
{
    use Data;
    use File;

    /**
     * @param $id
     * @throws Exception
     */
    public function init($id = 1)
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_EXPORTDATA_MM
        );
        $affectedRows = $queryBuilder
            ->update(Constants::TABLE_EXPORTDATA_MM)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'status',
                        Constants::HANDLER_FILEIMPORTER_START
                    ),
                    $queryBuilder->expr()->eq(
                        'action',
                        Constants::ACTION_IMPORT_FILE
                    ),
                    $queryBuilder->expr()->isNull(
                        'last_error'
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

        return $affectedRows > 0;
    }

    public function run()
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
     */
    protected function processImport(string $originalFileName, array $files): array
    {
        $response = [];
        foreach ($files as $fileStatus) {
            $instructionXmlPath = Environment::getPublicPath() . '/uploads/tx_l10nmgr/jobs/in/instruction.xml';
            if (file_exists($instructionXmlPath)) {
                unlink($instructionXmlPath);
            }
            $fileNameAndPath = $this->getLocalFilename($originalFileName, $fileStatus['locale']);
            $context = Environment::getContext()->__toString();
            $command = ($context ? ('TYPO3_CONTEXT=' . $context . ' ') : '') .
                CommandUtility::getCommand('php') . ' ' .
                Environment::getPublicPath() . '/typo3/sysext/core/bin/typo3 ' .
                'l10nmanager:import' .
                ' -t importFile' .
                ' --file ' . CommandUtility::escapeShellArgument($fileNameAndPath) . ' 2>&1';
            $statusCode = 200;
            $output = '';
            $action = CommandUtility::exec($command, $output, $statusCode);
            $response[] = [
                'http_status_code' => $statusCode,
                'response' => [
                    'action' => $action,
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
    protected function processResponse(int $uid, array $responses)
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
     */
    public function finish(int $time)
    {
        $this->dataFinish($time);
    }
}
