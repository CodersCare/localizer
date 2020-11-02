<?php

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\AddFileToMatrix;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Data;
use Localizationteam\Localizer\Language;
use Localizationteam\Localizer\Model\Repository\SelectorRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * FileExporter takes care to create file(s) that can be sent to Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 *
 */
class FileExporter extends AbstractCartHandler
{
    use AddFileToMatrix, Data, Language;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $uploadPath = '';

    /**
     * @var SelectorRepository
     */
    protected $selectorRepository;

    /**
     * @var array
     */
    protected $content = [];

    /**
     * @var array
     */
    protected $triples = [];

    /**
     * @var array
     */
    protected $exportTree = [];

    /**
     * @param $id
     * @throws Exception
     */
    public function init($id = 1)
    {
        $this->id = $id;
        $this->selectorRepository = GeneralUtility::makeInstance(SelectorRepository::class);
        parent::initProcessId();
        if ($this->acquire() === true) {
            $this->initRun();
        }
        if ($this->canRun()) {
            $this->initData();
            $this->loadCart();
        }
    }

    /**
     * @return bool
     */
    protected function acquire()
    {
        $acquired = false;
        $time = time();
        $affectedRows = GeneralUtility::makeInstance(ConnectionPool::class)
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
                    'uid' => (int)$this->id
                ],
                [
                    Connection::PARAM_INT,
                    Connection::PARAM_STR
                ]
            );

        if ($affectedRows > 0) {
            $acquired = true;
        }
        return $acquired;
    }

    /**
     *
     */
    public function run()
    {
        if ($this->canRun() === true) {
            $row = $this->data[0];
            if (isset($row['configuration'])) {
                $localizer = (int)$row['uid_local'];
                $cart = (int)$row['uid'];
                $configuration = (int)$row['uid_foreign'];
                $configurationData = BackendUtility::getRecord(
                    Constants::TABLE_L10NMGR_CONFIGURATION,
                    $configuration
                );
                $pid = (int)$configurationData['pid'];
                $cartConfiguration = json_decode($row['configuration'], true);
                if (!empty($cartConfiguration['languages']) && !empty($cartConfiguration['tables'])) {
                    $tables = $cartConfiguration['tables'];
                    $pageIds = $this->selectorRepository->loadAvailablePages($pid, $cart);
                    $this->content = $this->selectorRepository->getRecordsOnPages($pid, $pageIds, $tables);
                    $this->triples = $this->selectorRepository->loadStoredTriples($pageIds, $cart);
                    if (!empty($this->content) && !empty($this->triples)) {
                        foreach (array_keys($cartConfiguration['languages']) as $language) {
                            $configuredLanguageExport = $this->configureRecordsForLanguage(
                                $localizer,
                                $cart,
                                $configuration,
                                $language
                            );
                            if ($configuredLanguageExport) {
                                $this->processExport($configuration, $language);
                            }
                        }
                        $this->selectorRepository->updateL10nmgrConfiguration(
                            $configuration,
                            $localizer,
                            $cart,
                            $pageIds,
                            ''
                        );
                        $this->registerFilesForLocalizer($localizer, $configuration, $pid);
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

    /**
     * @param $localizer
     * @param $cart
     * @param $configuration
     * @param $language
     * @return bool
     */
    protected function configureRecordsForLanguage($localizer, $cart, $configuration, $language)
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
                $configuration,
                $localizer,
                $cart,
                $pageIds,
                $excludeItems
            );
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $table
     * @param $uid
     * @param $language
     */
    protected function checkReferences($table, $uid, $language)
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

    /**
     * @param $configuration
     * @param $language
     * @return array
     */
    protected function processExport($configuration, $language)
    {
        $context = Environment::getContext()->__toString();
        $command = ($context ? ('TYPO3_CONTEXT=' . $context . ' ') : '') .
            CommandUtility::getCommand('php') . ' ' .
            Environment::getPublicPath() . '/typo3/sysext/core/bin/typo3' .
            ' l10nmanager:export' .
            ' -c ' . CommandUtility::escapeShellArgument($configuration) .
            ' -t ' . CommandUtility::escapeShellArgument($language) . ' 2>&1'
        ;
        if ($this->getBackendUser()->user['realName']) {
            $command .= ' -customer ' . CommandUtility::escapeShellArgument($this->getBackendUser()->user['realName']);
        }

        $statusCode = 200;
        $output = '';
        $action = CommandUtility::exec($command, $output, $statusCode);
        return [
            'http_status_code' => $statusCode,
            'response' => [
                'action' => $action,
            ],
        ];
    }

    /**
     * @param int $localizerId
     * @param int $configurationId
     * @param int $pid
     */
    protected function registerFilesForLocalizer($localizerId, $configurationId, $pid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_L10NMGR_EXPORTDATA
        );
        $queryBuilder->getRestrictions()
            ->removeAll();
        $rows = $queryBuilder
            ->select('uid', 'translation_lang', 'filename')
            ->from(Constants::TABLE_L10NMGR_EXPORTDATA)
            ->where(
                $queryBuilder->expr()->eq(
                    'l10ncfg_id',
                    (int)$configurationId
                )
            )
            ->execute()
            ->fetchAll();
        if (empty($rows) === false) {
            foreach ($rows as $row) {
                $this->addFileToMatrix(
                    $pid,
                    $localizerId,
                    $row['uid'],
                    $configurationId,
                    $row['filename'],
                    $row['translation_lang'],
                    Constants::ACTION_SEND_FILE
                );
            }
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

    /**
     * @param int $uid
     * @param array $responses
     */
    protected function processResponses($uid, $responses)
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
     * @return bool|string
     */
    protected function getFileAndPath($fileName)
    {
        $file = $this->getUploadPath() . $fileName;
        return file_exists($file) ? $file : false;
    }

    /**
     * @return string
     */
    protected function getUploadPath()
    {
        if ($this->uploadPath === '') {
            $this->uploadPath = Environment::getPublicPath() . '/uploads/tx_l10nmgr/jobs/out/';
        }
        return $this->uploadPath;
    }
}