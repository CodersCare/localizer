<?php

namespace Localizationteam\Localizer\Task;

use Localizationteam\Localizer\Handler\AbstractHandler;
use Localizationteam\Localizer\Handler\FileSender;

/**
 * FileSenderTask sends files to Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 *
 */
class FileSenderTask extends AbstractTask
{
    /**
     * @var AbstractHandler
     */
    protected $handlerClass = FileSender::class;
}