<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Task;

use Localizationteam\Localizer\Handler\ErrorResetter;

/**
 * ErrorResetterTask resets task to previous state so it can be executed again
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class ErrorResetterTask extends AbstractTask
{
    protected string $handlerClass = ErrorResetter::class;
}
