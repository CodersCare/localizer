<?php

namespace Localizationteam\Localizer\Task;

use Localizationteam\Localizer\Handler\AbstractHandler;
use Localizationteam\Localizer\Handler\AutomaticExporter;

/**
 * FileSenderTask sends files to Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class AutomaticExporterTask extends AbstractTask
{
    /**
     * @var AbstractHandler
     */
    protected $handlerClass = AutomaticExporter::class;
}
