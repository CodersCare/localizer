<?php

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\Constants;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
     * @var string
     */
    protected $processId = '';
    /**
     * @var bool
     */
    private $run = false;
    /**
     * @var int
     */
    private $limit = 0;

    /**
     * @param $id
     * @throws Exception
     */
    abstract public function init($id = 1);

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
            ->getConnectionForTable(Constants::TABLE_EXPORTDATA_MM)
            ->update(
                Constants::TABLE_EXPORTDATA_MM,
                [
                    'tstamp' => $time,
                    'processid' => '',
                ],
                [
                    'processid' => $this->processId
                ],
                [
                    Connection::PARAM_INT,
                    Connection::PARAM_STR
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
     * @return bool
     */
    abstract protected function acquire();

    final protected function initProcessId()
    {
        $this->processId = md5(uniqid('', true) . (microtime(true) * 10000));
    }

    final protected function initRun()
    {
        $this->run = true;
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