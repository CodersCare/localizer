<?php

namespace Localizationteam\Localizer\Task;

use Exception;
use Localizationteam\Localizer\Handler\AbstractHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * AbstractTask
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class AbstractTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{
    /**
     * @var string
     */
    protected $handlerClass;

    /**
     * This is the main method that is called when a task is executed
     * It MUST be implemented by all classes inheriting from this one
     * Note that there is no error handling, errors and failures are expected
     * to be handled and logged by the client implementations.
     * Should return TRUE on successful execution, FALSE on error.
     *
     * @return bool Returns TRUE on successful execution, FALSE on error
     * @throws Exception
     */
    public function execute(): bool
    {
        /** @var AbstractHandler $handler */
        $handler = GeneralUtility::makeInstance($this->handlerClass);
        $handler->init();
        $handler->run();
        return true;
    }
}
