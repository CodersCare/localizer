<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Task;

use Localizationteam\Localizer\Handler\FileDownloader;

/**
 * FileDownloaderTask downloads files from Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class FileDownloaderTask extends AbstractTask
{
    /**
     * @var string
     */
    protected string $handlerClass = FileDownloader::class;
}
