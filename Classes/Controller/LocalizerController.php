<?php

namespace Localizationteam\Localizer\Controller;

use TYPO3\CMS\Backend\Module\BaseScriptClass;

/**
 * Module 'Localizer' for the 'l10n_matrix' extension.
 *
 * @author    Jo Hasenau <info@cybercraft.de>
 * @package    TYPO3
 * @subpackage    localizer
 */
class LocalizerController extends BaseScriptClass
{
    function main()
    {

    }

    function printContent()
    {
        echo $this->content;
    }
}