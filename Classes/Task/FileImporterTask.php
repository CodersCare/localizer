<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Task;

use Localizationteam\Localizer\Handler\FileImporter;

/**
 * FileImporterTask imports files to TYPO3
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class FileImporterTask extends AbstractTask
{
    protected string $handlerClass = FileImporter::class;
}
