<?php

namespace Localizationteam\Localizer\Task;

use Localizationteam\Localizer\Handler\AbstractHandler;
use Localizationteam\Localizer\Handler\StatusRequester;

/**
 * StatusRequesterTask requests translation status from remote servers
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class StatusRequesterTask extends AbstractTask
{
    /**
     * @var AbstractHandler
     */
    protected $handlerClass = StatusRequester::class;
}
