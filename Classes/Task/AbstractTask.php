<?php

namespace Localizationteam\Localizer\Task;

use Exception;
use Localizationteam\Localizer\Handler\AbstractHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * AbstractTask $COMMENT$
 *
 * @author      Peter Russ<peter.russ@4many.net>
 * @package     TYPO3
 * @date        20150920-1107
 * @subpackage  localizer
 *
 */
class AbstractTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{

    /**
     * @var AbstractHandler
     */
    protected $handlerClass;

    /**
     * This is the main method that is called when a task is executed
     * It MUST be implemented by all classes inheriting from this one
     * Note that there is no error handling, errors and failures are expected
     * to be handled and logged by the client implementations.
     * Should return TRUE on successful execution, FALSE on error.
     *
     * @return boolean Returns TRUE on successful execution, FALSE on error
     * @throws Exception
     */
    public function execute()
    {
        /** @var AbstractHandler $handler */
        $handler = GeneralUtility::makeInstance($this->handlerClass);
        $handler->init();
        $handler->run();
        return true;
    }
}