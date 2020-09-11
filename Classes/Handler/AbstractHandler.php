<?php

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\Constants;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * AbstractHandler $COMMENT$
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 *
 */
abstract class AbstractHandler
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
     * @var int
     */
    private $limit = 0;

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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_EXPORTDATA_MM);
        $queryBuilder->getRestrictions();
        $queryBuilder
            ->update(Constants::TABLE_EXPORTDATA_MM)
            ->where(
                $queryBuilder->expr()->eq(
                    'processid',
                    $queryBuilder->createNamedParameter($this->processId, PDO::PARAM_STR)
                )
            )
            ->set('tstamp', $time)
            ->set('processid', $this->processId)
            ->execute();
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

    /**
     * @param int $limit
     */
    protected function setLimit($limit)
    {
        $this->limit = $limit;
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