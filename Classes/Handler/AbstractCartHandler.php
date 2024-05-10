<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\Constants;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * AbstractCartHandler $COMMENT$
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
abstract class AbstractCartHandler
{
    protected string $processId = '';
    private bool $run = false;

    /**
     * @throws Exception
     */
    abstract public function init(int $id = 1);

    /**
     * @return mixed
     */
    abstract public function run();

    final public function __destruct()
    {
        $time = time();
        $this->finish($time);
        $this->releaseAcquiredItems($time);
    }

    abstract public function finish(int $time);

    protected function releaseAcquiredItems(int $time = 0): void
    {
        if ($time == 0) {
            $time = time();
        }
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(Constants::TABLE_LOCALIZER_CART)
            ->update(
                Constants::TABLE_LOCALIZER_CART,
                [
                    'tstamp' => $time,
                    'processid' => '',
                ],
                [
                    'processid' => $this->processId,
                ],
                [
                    Connection::PARAM_INT,
                    Connection::PARAM_STR,
                ]
            );
    }

    final public function getProcessId(): string
    {
        return $this->processId;
    }

    abstract protected function acquire(): bool;

    final protected function initProcessId(): void
    {
        $this->processId = md5(uniqid('', true) . (microtime(true) * 10000));
    }

    final protected function initRun(): void
    {
        $this->run = true;
    }

    final protected function resetRun(): void
    {
        $this->run = false;
    }

    final protected function canRun(): bool
    {
        return $this->run;
    }
}
