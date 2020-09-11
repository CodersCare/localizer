<?php

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\Constants;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * AbstractCartHandler $COMMENT$
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 *
 */
abstract class AbstractCartHandler
{
    /**
     * @var bool
     */
    private $run = false;

    /**
     * @var string
     */
    protected $processId = '';

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

    final protected function initProcessId()
    {
        $this->processId = md5(uniqid('', true) . (microtime(true) * 10000));
    }

    /**
     * @return bool
     */
    protected function acquire()
    {
        $acquired = false;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_CART);
        $queryBuilder->getRestrictions();
        $affectedRows = $queryBuilder
            ->update(Constants::TABLE_LOCALIZER_CART)
            ->where(
                $this->acquireWhere
            )
            ->set('tstamp', time())
            ->set('processid', $this->processId)
            ->execute();
        if ($affectedRows > 0) {
            $acquired = true;
        }
        return $acquired;
    }

    final protected function initRun()
    {
        $this->run = true;
    }

    abstract function run();

    final public function __destruct()
    {
        $time = time();
        $this->finish($time);
        $this->releaseAcquiredItems($time);
    }

    /**
     * @param int $time
     * @return void
     */
    abstract function finish($time);

    /**
     * @param int $time
     */
    protected function releaseAcquiredItems($time = 0)
    {
        if ($time == 0) {
            $time = time();
        }
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(Constants::TABLE_LOCALIZER_CART)
            ->update(
                Constants::TABLE_LOCALIZER_CART,
                [
                    'processid' => $this->processId
                ],
                [
                    'tstamp' => $time,
                    'processid' => '',
                ],
                [
                    PDO::PARAM_INT,
                    PDO::PARAM_STR
                ]
            );

    }

    /**
     * @return string
     */
    final function getProcessId()
    {
        return $this->processId;
    }

    /**
     * @param ExpressionBuilder $where
     */
    protected function setAcquireWhere($where)
    {
        $this->acquireWhere = $where;
    }

    final protected function resetRun()
    {
        $this->run = false;
    }

    /**
     * @return bool
     */
    final protected function canRun()
    {
        return (bool)$this->run;
    }
}