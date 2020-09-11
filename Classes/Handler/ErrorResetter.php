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
 * @package     TYPO3
 * @subpackage  localizer
 *
 */
class ErrorResetter extends AbstractHandler
{
    /**
     * @param $id
     * @throws Exception
     */
    public function init($id = 1)
    {
        parent::init($id);
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
                        $queryBuilder->createNamedParameter(Constants::STATUS_CART_ERROR, PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->neq(
                        'previous_status',
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
            ->execute();
        if ($affectedRows > 0) {
            $acquired = true;
        }
        return $acquired;
    }

    public function run()
    {
        if ($this->canRun() === true) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_EXPORTDATA_MM);
            $queryBuilder->getRestrictions();
            $queryBuilder
                ->update(Constants::TABLE_EXPORTDATA_MM)
                ->where(
                    $queryBuilder->expr()->eq(
                        'last_error',
                        $queryBuilder->createNamedParameter('', PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->gt(
                        'previous_status',
                        $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'processid',
                        $queryBuilder->createNamedParameter($this->getProcessId(), PDO::PARAM_STR)
                    )
                )
                ->set('status', $queryBuilder->quoteIdentifier('previous_status'))
                ->set('previous_status', 0)
                ->set('last_error', '')
                ->execute();
        }
    }

    /**
     * @param int $time
     * @return void
     */
    function finish($time)
    {
        // nothing to do here
    }
}