<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Module 'Settings'
 *
 * Just shows a shortcut link to the list module with selected settings table.
 */
class SettingsController
{
    protected string $moduleName = 'localizer_localizersettings';
    protected ModuleTemplate $moduleTemplate;

    public function __construct(ModuleTemplate $moduleTemplate)
    {
        $this->moduleTemplate = $moduleTemplate;
    }

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)$request->getQueryParams()['id'];
        $content = $this->moduleTemplate->header('LOCALIZER Settings');
        if ($id > 0) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $listViewLink = $uriBuilder->buildUriFromRoute('web_list', ['id' => $id, 'table' => 'tx_localizer_settings']);
            $content .= '<a href="' . $listViewLink . '" class="btn btn-primary">Switch to List Module</a>';
        } else {
            $content .= '<div class="error">Please select a page</div>';
        }

        $this->moduleTemplate->setContent($content);
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }
}
