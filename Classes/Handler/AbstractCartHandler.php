<?php

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\DatabaseConnection;

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
    use DatabaseConnection;

    /**
     * @var bool
     */
    private $run = false;

    /**
     * @var string
     */
    private $processId = '';

    /**
     * @var string
     */
    private $acquireWhere = '';

    /**
     * @param $id
     * @throws Exception
     */
    public function init($id = 1)
    {
        if ($this->acquireWhere !== '' && $id) {
            $this->initProcessId();
            if ($this->acquire() === true) {
                $this->initRun();
            }
        } else {
            throw new Exception('Condition for acquire() missing');
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
        $fields = [
            'tstamp'    => time(),
            'processid' => $this->processId,
        ];
        $this->getDatabaseConnection()
            ->exec_UPDATEquery(
                Constants::TABLE_LOCALIZER_CART,
                $this->acquireWhere,
                $fields
            );
        if ($this->getDatabaseConnection()->sql_affected_rows() > 0) {
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
        $this->getDatabaseConnection()->exec_UPDATEquery(
            Constants::TABLE_LOCALIZER_CART,
            'processid = "' . $this->processId . '"',
            [
                'tstamp'    => $time,
                'processid' => '',
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
     * @param $where
     */
    protected function setAcquireWhere($where)
    {
        $this->acquireWhere = (string)$where;
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