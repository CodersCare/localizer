<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Data;
use Localizationteam\Localizer\Language;
use Localizationteam\Localizer\Runner\ReportSuccess;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * SuccessReporter takes care to report back a successful import of a translation to the remote server
 *
 * @author      Jo Hasenau<jh@cybercraft.de>
 */
class SuccessReporter extends AbstractHandler
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

    protected function acquire(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_EXPORTDATA_MM
        );
        $affectedRows = $queryBuilder
            ->update(Constants::TABLE_EXPORTDATA_MM)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->gte(
                        'status',
                        Constants::HANDLER_SUCCESSREPORTER_START
                    ),
                    $queryBuilder->expr()->lt(
                        'status',
                        Constants::HANDLER_SUCCESSREPORTER_FINISH
                    ),
                    $queryBuilder->expr()->eq(
                        'action',
                        Constants::ACTION_REPORT_SUCCESS
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
                $configuration = array_merge(
                    $localizerSettings,
                    [
                        'uid' => $row['uid'],
                        'file' => $row['filename'],
                        'target' => $this->getIso2ForLocale($row),
                    ]
                );
                /** @var ReportSuccess $runner */
                $runner = GeneralUtility::makeInstance(ReportSuccess::class);
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
                    DebugUtility::debug($response, __LINE__);
                    //todo: more error handling
                }
            }
        }
    }

    /**
     * @param int $uid
     * @param mixed $response
     */
    protected function processResponse(int $uid, $response)
    {
        if (isset($response['status'])) {
            $this->addSuccessResult($uid, $response['status'], 0, $response);
        }
    }

    public function finish(int $time): void
    {
        $this->dataFinish($time);
    }
}
