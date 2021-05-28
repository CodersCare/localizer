<?php

namespace Localizationteam\Localizer\Task;

use Localizationteam\Localizer\Handler\AbstractHandler;
use Localizationteam\Localizer\Handler\FileDownloader;

/**
 * FileDownloaderTask downloads files from Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class FileDownloaderTask extends AbstractTask
{
    /**
     * @var AbstractHandler
     */
    protected $handlerClass = FileDownloader::class;
}
