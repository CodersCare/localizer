<?php

namespace Localizationteam\Localizer\Task;

use Localizationteam\Localizer\Handler\AbstractHandler;
use Localizationteam\Localizer\Handler\StatusRequester;

/**
 * StatusRequesterTask sends files to Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>
 * @package     TYPO3
 * @date        20150910-2009
 * @subpackage  localizer
 *
 */
class StatusRequesterTask extends AbstractTask
{
    /**
     * @var AbstractHandler
     */
    protected $handlerClass = StatusRequester::class;
}