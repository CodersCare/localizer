<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Handler;

use Doctrine\DBAL\DBALException;
use Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Model\Repository\SelectorRepository;
use Localizationteam\Localizer\Traits\AddFileToMatrix;
use Localizationteam\Localizer\Traits\Data;
use Localizationteam\Localizer\Traits\Language;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * FileExporter takes care to create file(s) that can be sent to Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class FileExporter extends AbstractCartHandler
{
    use AddFileToMatrix;
    use Data;
    use Language;

    protected int $id;

    protected string $uploadPath = '';

    protected SelectorRepository $selectorRepository;

    protected array $content = [];

    protected array $triples = [];

    protected array $exportTree = [];

    /**
     * @throws Exception
     */
    public function init(int $id = 1): void
    {
        $this->id = $id;
        $this->selectorRepository = GeneralUtility::makeInstance(SelectorRepository::class);
        $this->initProcessId();
        if ($this->acquire()) {
            $this->initRun();
        }
        if ($this->canRun()) {
            $this->initData();
            $this->loadCart();
        }
    }

    protected function acquire(): bool
    {
        $time = time();
        $affectedRows = self::getConnectionPool()
            ->getConnectionForTable(Constants::TABLE_LOCALIZER_CART)
            ->update(
                Constants::TABLE_LOCALIZER_CART,
                [
                    'tstamp' => $time,
                    'processid' => $this->processId,
                ],
                [
                    'deleted' => 0,
                    'hidden' => 0,
                    'status' => Constants::HANDLER_FILEEXPORTER_START,
                    'action' => Constants::ACTION_EXPORT_FILE,
                    'last_error' => null,
                    'processid' => '',
                    'uid' => $this->id,
                ],
                [
                    Connection::PARAM_INT,
                    Connection::PARAM_STR,
                ]
            );

        return $affectedRows > 0;
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        if ($this->canRun() === true) {
            $row = $this->data[0];
            if (isset($row['configuration'])) {
                $localizerId = (int)$row['uid_local'];
                $cartId = (int)$row['uid'];
                $configurationId = (int)$row['uid_foreign'];
                $configurationData = BackendUtility::getRecord(
                    Constants::TABLE_L10NMGR_CONFIGURATION,
                    $configurationId
                );
                $pid = (int)$configurationData['pid'];
                $cartConfiguration = json_decode($row['configuration'], true);
                if (!empty($cartConfiguration['languages']) && !empty($cartConfiguration['tables'])) {
                    $tables = $cartConfiguration['tables'];
                    $pageIds = $this->selectorRepository->loadAvailablePages($pid, $cartId);
                    $languageIds = array_keys($cartConfiguration['languages']);
                    $this->content = $this->selectorRepository->getRecordsOnPages($pid, $pageIds, $tables, [], $languageIds);
                    $this->triples = $this->selectorRepository->loadStoredTriples($pageIds, $cartId);
                    if (!empty($this->content) && !empty($this->triples)) {
                        foreach ($languageIds as $languageId) {
                            $configuredLanguageExport = $this->configureRecordsForLanguage(
                                $localizerId,
                                $cartId,
                                $configurationId,
                                $languageId
                            );
                            if ($configuredLanguageExport) {
                                $output = $this->processExport($configurationId, $languageId);
                                if ($output['exitCode'] > 0) {
                                    throw new Exception(
                                        'Failed export to file with: "' . $output['command'] . '". Exit code was: "' . $output['exitCode'] . '". Output was: "' . $output['output'] . '".',
                                        1625730835
                                    );
                                }
                            }
                        }
                        $this->selectorRepository->updateL10nmgrConfiguration(
                            $configurationId,
                            $localizerId,
                            $cartId,
                            $pageIds,
                            ''
                        );
                        $this->registerFilesForLocalizer($localizerId, $configurationId, $pid);
                    }
                }
            } else {
                $this->addErrorResult(
                    $row['uid'],
                    Constants::STATUS_CART_ERROR,
                    Constants::HANDLER_FILEEXPORTER_ERROR_STATUS_RESET,
                    'Insufficient information found in cart entry.',
                    Constants::HANDLER_FILEEXPORTER_ERROR_ACTION_RESET
                );
            }
        }
    }

    protected function configureRecordsForLanguage(int $localizer, int $cart, int $configurationId, int $language): bool
    {
        $this->exportTree = [];
        if (!empty($this->content['records'])) {
            foreach ($this->content['records'] as $table => $records) {
                if (!empty($records)) {
                    foreach ($records as $uid => $record) {
                        $identifier = md5($table . '.' . $uid . '.' . $language);
                        if (empty($this->triples[$identifier])) {
                            $this->exportTree[] = $table . ':' . $uid;
                        }
                        if (!empty($this->content['referencedRecords'][$table][$uid])) {
                            $this->checkReferences($table, $uid, $language);
                        }
                    }
                }
            }
        }
        if (!empty($this->triples)) {
            $excludeItems = implode(',', $this->exportTree);
            $pageIds = $this->selectorRepository->loadAvailablePages(0, $cart);
            $this->selectorRepository->updateL10nmgrConfiguration(
                $configurationId,
                $localizer,
                $cart,
                $pageIds,
                $excludeItems
            );
            return true;
        }
        return false;
    }

    protected function checkReferences(string $table, int $uid, int $language)
    {
        foreach ($this->content['referencedRecords'][$table][$uid] as $referencedTable => $records) {
            if (!empty($records)) {
                foreach ($records as $record) {
                    $referencedUid = (int)$record['uid'];
                    $identifier = md5($referencedTable . '.' . $referencedUid . '.' . $language);
                    if (empty($this->triples[$identifier])) {
                        $this->exportTree[] = $referencedTable . ':' . $referencedUid;
                    }
                    if (!empty($this->content['referencedRecords'][$referencedTable][$referencedUid])) {
                        $this->checkReferences($referencedTable, $referencedUid, $language);
                    }
                }
            }
        }
    }

    protected function processExport(int $configurationId, int $languageId): array
    {
        $commandRegistry = GeneralUtility::makeInstance(CommandRegistry::class);
        $l10nManagerExportCommand = $commandRegistry->getCommandByIdentifier('l10nmanager:export');
        $arguments = [
            '-c' => (string)$configurationId,
            '-t' => (string)$languageId,
        ];
        if ($this->getBackendUser()->user['realName']) {
            $arguments['--customer'] = $this->getBackendUser()->user['realName'];
        }
        $input = new ArrayInput($arguments);
        $input->setInteractive(false);
        $output = new BufferedOutput();
        $result = $l10nManagerExportCommand->run($input, $output);

        return [
            'exitCode' => $result,
            'command' => 'l10nmanager:export ' . $input,
            'output' => $output->fetch(),
        ];
    }

    /**
     * @throws DBALException
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected function registerFilesForLocalizer(int $localizerId, int $configurationId, int $pid): void
    {
        $queryBuilder = self::getConnectionPool()
            ->getQueryBuilderForTable(Constants::TABLE_L10NMGR_EXPORTDATA);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid', 'translation_lang', 'source_lang', 'filename')
            ->from(Constants::TABLE_L10NMGR_EXPORTDATA)
            ->where(
                $queryBuilder->expr()->eq(
                    'l10ncfg_id',
                    $configurationId
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();


            foreach ($rows as $row) {
                $this->addFileToMatrix(
                    $pid,
                    $localizerId,
                    $row['uid'],
                    $configurationId,
                    $row['filename'],
                    (int)$row['translation_lang'],
                    (int)$row['source_lang'],
                    Constants::ACTION_SEND_FILE,

                );
            }

    }

    public function finish(int $time): void
    {
        $this->dataFinish($time);
    }

    protected function processResponses(int $uid, array $responses)
    {
        $success = true;
        foreach ($responses as $response) {
            if ($response['http_status_code'] > 399) {
                $success = false;
            }
        }
        if ($success === true) {
            $this->addSuccessResult(
                $uid,
                Constants::STATUS_CART_FILE_EXPORTED,
                Constants::ACTION_SEND_FILE
            );
        } else {
            $this->addErrorResult(
                $uid,
                Constants::STATUS_CART_ERROR,
                0,
                'Error while exporting File'
            );
        }
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
}
