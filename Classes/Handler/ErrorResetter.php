<?php

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\Constants;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ErrorResetter resets status in Localizer cart to status before error occured so that this can rerun.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class ErrorResetter extends AbstractHandler
{
    /**
     * @param $id
     * @throws Exception
     */
    public function init($id = 1)
    {
        $this->initProcessId();
        if ($this->acquire() === true) {
            $this->initRun();
        }
    }

    /**
     * @return bool
     */
    protected function acquire()
    {
        $acquired = false;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_EXPORTDATA_MM
        );
        $affectedRows = $queryBuilder
            ->update(Constants::TABLE_EXPORTDATA_MM)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'status',
                        Constants::STATUS_CART_ERROR
                    ),
                    $queryBuilder->expr()->gt(
                        'previous_status',
                        0
                    ),
                    $queryBuilder->expr()->eq(
                        'processid',
                        $queryBuilder->createNamedParameter('', PDO::PARAM_STR)
                    )
                )
            )
            ->set('tstamp', time())
            ->set('processid', $this->processId)
            ->execute();
        if ($affectedRows > 0) {
            $acquired = true;
        }
        return $acquired;
    }

    public function run()
    {
        if ($this->canRun() === true) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
                Constants::TABLE_EXPORTDATA_MM
            );
            $queryBuilder
                ->update(Constants::TABLE_EXPORTDATA_MM)
                ->where(
                    $queryBuilder->expr()->isNull(
                        'last_error'
                    ),
                    $queryBuilder->expr()->gt(
                        'previous_status',
                        0
                    ),
                    $queryBuilder->expr()->eq(
                        'processid',
                        $queryBuilder->createNamedParameter($this->getProcessId(), PDO::PARAM_STR)
                    )
                )
                ->set('status', $queryBuilder->quoteIdentifier('previous_status'))
                ->set('previous_status', 0)
                ->set('last_error', null)
                ->execute();
        }
    }

    /**
     * @param int $time
     */
    public function finish($time)
    {
        // nothing to do here
    }
}
