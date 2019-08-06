<?php

namespace Localizationteam\Localizer\Task;

use Localizationteam\Localizer\Handler\AbstractHandler;
use Localizationteam\Localizer\Handler\StatusRequester;

/**
 * StatusRequesterTask sends files to Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
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