<?php

namespace Localizationteam\Localizer\Task;

use Localizationteam\Localizer\Handler\AbstractHandler;
use Localizationteam\Localizer\Handler\SuccessReporter;

/**
 * ReporterTask reports back to remote server if the import was successful
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class SuccessReporterTask extends AbstractTask
{
    /**
     * @var AbstractHandler
     */
    protected $handlerClass = SuccessReporter::class;
}
