<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Task;

use Localizationteam\Localizer\Handler\FileSender;

/**
 * FileSenderTask sends files to Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class FileSenderTask extends AbstractTask
{
    /**
     * @var string
     */
    protected string $handlerClass = FileSender::class;
}
