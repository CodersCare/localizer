<?php

namespace Localizationteam\Localizer\Task;

use Localizationteam\Localizer\Handler\AbstractHandler;
use Localizationteam\Localizer\Handler\ErrorResetter;

/**
 * ErrorResetterTask resets task to previous state so it can be executed again
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 *
 */
class ErrorResetterTask extends AbstractTask
{
    /**
     * @var AbstractHandler
     */
    protected $handlerClass = ErrorResetter::class;
}