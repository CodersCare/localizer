<?php

namespace Localizationteam\Localizer\Task;

use Localizationteam\Localizer\Handler\AbstractHandler;
use Localizationteam\Localizer\Handler\FileSender;

/**
 * FileSenderTask sends files to Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>
 * @package     TYPO3
 * @date        20150910-2009
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