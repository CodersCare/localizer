<?php

namespace Localizationteam\Localizer\Task;

use Localizationteam\Localizer\Handler\AbstractHandler;
use Localizationteam\Localizer\Handler\ErrorResetter;

/**
 * ErrorResetterTask resets task to previous state so it can be executed again
 *
 * @author      Peter Russ<peter.russ@4many.net>
 * @package     TYPO3
 * @date        20150910-2009
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