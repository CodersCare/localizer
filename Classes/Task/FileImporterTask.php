<?php

namespace Localizationteam\Localizer\Task;

use Localizationteam\Localizer\Handler\AbstractHandler;
use Localizationteam\Localizer\Handler\FileImporter;

/**
 * FileImporterTask imports files to TYPO3
 *
 * @author      Peter Russ<peter.russ@4many.net>
 * @package     TYPO3
 * @date        20150910-2009
 * @subpackage  localizer
 *
 */
class FileImporterTask extends AbstractTask
{
    /**
     * @var AbstractHandler
     */
    protected $handlerClass = FileImporter::class;
}