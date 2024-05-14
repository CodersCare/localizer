<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Http\HtmlResponse;

/**
 * Abstract for modules of the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
abstract class AbstractController extends BaseModule
{
    protected array $pageinfo;

    protected int $localizerId;

    protected int $localizerPid;

    protected array $legend = [];

    protected array $statusClasses = [];

    protected array $availableLocalizers;

    protected ModuleTemplate $moduleTemplate;

    /**
     * Injects the request object for the current request or subrequest
     * Then checks for module functions that have hooked in, and renders menu etc.
     *
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);

        $this->init($request);
        $this->main($request);

        $this->moduleTemplate->setContent($this->content);

        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Initializing the module
     */
    public function init(ServerRequestInterface $request): array
    {
        $localizer = [];
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(1);
        $this->id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? null);
        $this->availableLocalizers = $this->abstractRepository->loadAvailableLocalizers();
        $this->localizerId = (int)($request->getParsedBody()['selected_localizer'] ?? $request->getQueryParams()['selected_localizer'] ?? null);
        $this->localizerPid = (int)($request->getParsedBody()['selected_localizerPid'] ?? $request->getQueryParams()['selected_localizerPid'] ?? null);

        $pageTree = $this->getBackendUser()->uc['BackendComponents']['States']['Pagetree'] ?? null;
        if (empty($this->id) && is_object($pageTree)) {
            $this->id = hexdec(
                ltrim(
                    (string)$pageTree->stateHash->lastSelectedNode,
                    'p'
                )
            );
        }

        $this->legend = [
            '10' => [
                'cssClass' => 'changed-after-translation',
                'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:legend.changed.after.translation',
            ],
            '20' => [
                'cssClass' => 'not-translated',
                'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:legend.not.translated',
            ],
            '30' => [
                'cssClass' => 'sent-to-translation',
                'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:legend.sent.to.translation',
            ],
            '40' => [
                'cssClass' => 'back-from-translation',
                'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:legend.back.from.translation',
            ],
            '50' => [
                'cssClass' => 'translated',
                'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:legend.translated',
            ],
        ];

        $this->statusClasses = [
            '0' => $this->legend['20'],
            '10' => $this->legend['20'],
            '15' => $this->legend['20'],
            '17' => $this->legend['30'],
            '20' => $this->legend['30'],
            '30' => $this->legend['30'],
            '40' => $this->legend['30'],
            '50' => $this->legend['30'],
            '60' => $this->legend['40'],
            '70' => $this->legend['50'],
            '71' => $this->legend['10'],
            '80' => $this->legend['50'],
            '90' => $this->legend['50'],
        ];

        if (!empty($this->localizerId) && !empty($this->availableLocalizers[$this->localizerId])) {
            $localizer = $this->availableLocalizers[$this->localizerId];
            $this->getLocalizerSettings($localizer['type']);
        }
        return $localizer;
    }

    /**
     * Override default settings based on registered type settings for a specific localizer
     */
    protected function getLocalizerSettings($type)
    {
    }

    /**
     * Main function, starting the rendering of the list.
     */
    abstract protected function main(ServerRequestInterface $request);

    /**
     * Initialize function menu array
     */
    public function menuConfig(): void
    {
        $this->MOD_MENU = [
            'bigControlPanel' => '',
            'clipBoard' => '',
            'localization' => '',
        ];
    }
}
