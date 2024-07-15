<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Events;

use Localizationteam\Localizer\Handler\AbstractHandler;

class HandlerRunHasFinished
{
    private AbstractHandler $handler;
    private array $result;

    public function __construct(AbstractHandler $handler, array $result)
    {
        $this->handler = $handler;
        $this->result = $result;
    }

    public function getHandler(): AbstractHandler
    {
        return $this->handler;
    }

    public function getResult(): array
    {
        return $this->result;
    }

    public function setResult(array $result): void
    {
        $this->result = $result;
    }
}
