<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Controller;

/**
 * Module 'Localizer' for the 'l10n_matrix' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class LocalizerController extends BaseModule
{
    public function main(): void
    {
    }

    public function printContent(): void
    {
        echo $this->content;
    }
}
