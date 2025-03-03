<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Handler;

use Doctrine\DBAL\DBALException;
use Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Runner\RequestStatus;
use Localizationteam\Localizer\Traits\Data;
use Localizationteam\Localizer\Traits\Language;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * StatusRequester takes care to request the translation status for file(s) from Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class StatusRequester extends AbstractHandler
{
    use Data;
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
                    $queryBuilder->expr()->gte(
                        'status',
                        Constants::HANDLER_STATUSREQUESTER_START
                    ),
                    $queryBuilder->expr()->lt(
                        'status',
                        Constants::HANDLER_STATUSREQUESTER_FINISH
                    ),
                    $queryBuilder->expr()->eq(
                        'action',
                        Constants::ACTION_REQUEST_STATUS
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
            ->executeStatement();

        return $affectedRows > 0;
    }

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
            } else {
                $target = $this->getIso2ForLocale($row);
                if (empty($target)) {
                    continue;
                }
                $configuration = array_merge(
                    $localizerSettings,
                    [
                        'uid' => $row['uid'],
                        'file' => $row['filename'],
                        'target' => $target,
                    ]
                );
                /** @var RequestStatus $runner */
                $runner = GeneralUtility::makeInstance(RequestStatus::class);
                $runner->init($configuration);
                $runner->run($configuration);
                $response = $runner->getResponse();
                if (isset($response['http_status_code'])) {
                    if ($response['http_status_code'] == 200) {
                        $this->processResponse($row['uid'], $response);
                    } else {
                        DebugUtility::debug($response, 'ERROR');
                    }
                } else {
                    DebugUtility::debug($response, (string)__LINE__);
                    //todo: more error handling
                }
            }
        }

        $this->result = $this->dispatchHandlerRunHasFinishedEvent($this->result);
    }

    /**
     * @param mixed $response
     */
    protected function processResponse(int $uid, $response)
    {
        $translationStatus = 0;
        if (isset($response['files'])) {
            foreach ($response['files'] as $fileStatus) {
                if ((int)$fileStatus['status'] > $translationStatus) {
                    $translationStatus = (int)$fileStatus['status'];
                }
            }
            $action = Constants::ACTION_REQUEST_STATUS;
            $status = Constants::STATUS_CART_TRANSLATION_IN_PROGRESS;
            $originalResponse = [];
            switch ($translationStatus) {
                case Constants::API_TRANSLATION_STATUS_IN_PROGRESS:
                case Constants::API_TRANSLATION_STATUS_WAITING:
                    $status = Constants::STATUS_CART_TRANSLATION_IN_PROGRESS;
                    break;
                case Constants::API_TRANSLATION_STATUS_TRANSLATED:
                    $status = Constants::STATUS_CART_TRANSLATION_FINISHED;
                    $action = Constants::ACTION_DOWNLOAD_FILE;
                    $originalResponse = $response;
                    break;
            }
            $this->addSuccessResult($uid, $status, $action, $originalResponse);
        }
    }

    public function finish(int $time): void
    {
        $this->dataFinish($time);
    }
}
