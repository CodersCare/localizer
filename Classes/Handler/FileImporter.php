<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Traits\Data;
use Localizationteam\Localizer\Traits\File;
use Localizationteam\Localizer\Traits\Language;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
                        $queryBuilder->createNamedParameter('')
                    )
                )
            )
            ->set('tstamp', time())
            ->set('processid', $this->processId)
            ->setMaxResults(Constants::HANDLER_FILEIMPORTER_MAX_FILES)
            ->executeStatement();

        return $affectedRows > 0;
    }

    /**
     * @throws Exception
     */
    public function run(): void
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
                    } elseif (isset($originalResponse['files'])) {
                        $response = $this->processImport($row, $originalResponse['files']);
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
        }
    }

    /**
     * @throws Exception
     */
    protected function processImport(array $row, array $files): array
    {
        $response = [];
        $instructionXmlPath = Environment::getPublicPath() . '/uploads/tx_l10nmgr/jobs/in/instruction.xml';
        if (file_exists($instructionXmlPath)) {
            unlink($instructionXmlPath);
        }
        $iso2 = $this->getIso2ForLocale($row);
        $fileNameAndPath = $this->getLocalFilename($row['filename'], $iso2);
        $commandRegistry = GeneralUtility::makeInstance(CommandRegistry::class);
        $l10nmanagerImportCommand = $commandRegistry->getCommandByIdentifier('l10nmanager:import');
        $arguments = [
            '-t' => 'importFile',
            '--file' => $fileNameAndPath,
        ];
        $input = new ArrayInput($arguments);
        $input->setInteractive(false);
        $output = new BufferedOutput();
        $l10nmanagerImportCommand->run($input, $output);
        $response[] = [
            'http_status_code' => 200,
            'response' => [
                'action' => 'l10nmanager:import ' . $input,
                'file' => $row['filename'],
                'locale' => $iso2,
            ],
        ];
        return $response;
    }

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
                Constants::ACTION_REPORT_SUCCESS
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

    public function finish(int $time): void
    {
        $this->dataFinish($time);
    }
}
