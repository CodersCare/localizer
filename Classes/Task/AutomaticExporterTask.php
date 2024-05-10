<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Task;

use Localizationteam\Localizer\Handler\AutomaticExporter;

/**
 * FileSenderTask exports records marked for automatic export
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class AutomaticExporterTask extends AbstractTask
{
    protected string $handlerClass = AutomaticExporter::class;
}
