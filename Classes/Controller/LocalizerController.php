<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Controller;

use TYPO3\CMS\Backend\Attribute\Controller;

/**
 * Module 'Localizer' for the 'l10n_matrix' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @todo With the new ModuleAPI, this class is not needed anymore and can be removed when dropping support for v11.
 */
#[Controller]
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
