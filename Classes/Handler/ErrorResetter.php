<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Handler;

use Doctrine\DBAL\DBALException;
use Exception;
use Localizationteam\Localizer\Constants;
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
        if ($this->acquire()) {
            $this->initRun();
        }
    }

    /**
     * @return bool
     * @throws DBALException
     */
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
                        Constants::STATUS_CART_ERROR
                    ),
                    $queryBuilder->expr()->gt(
                        'previous_status',
                        0
                    ),
                    $queryBuilder->expr()->eq(
                        'processid',
                        $queryBuilder->createNamedParameter('')
                    )
                )
            )
            ->set('tstamp', time())
            ->set('processid', $this->processId)
            ->execute();

        return (int)$affectedRows > 0;
    }

    /**
     * @throws DBALException
     */
    public function run(): void
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
                        $queryBuilder->createNamedParameter($this->getProcessId())
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
    public function finish(int $time)
    {
        // nothing to do here
    }
}
