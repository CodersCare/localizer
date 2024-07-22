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
    public function init($id = 1): void
    {
        $this->initProcessId();
        if ($this->acquire()) {
            $this->initRun();
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
            ->executeStatement();

        return $affectedRows > 0;
    }

    /**
     * @throws DBALException
     */
    public function run(): void
    {
        if (!$this->canRun()) {
            return;
        }

        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(
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
            ->executeStatement();
    }

    public function finish(int $time): void
    {
        // nothing to do here
    }
}
