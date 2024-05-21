<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Controller;

use Localizationteam\L10nmgr\LanguagesService;
use Localizationteam\Localizer\Traits\BackendUserTrait;
use Localizationteam\Localizer\Traits\LanguageServiceTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Module 'Settings'
 *
 * Just shows a shortcut link to the list module with selected settings table.
 */
#[Controller]
class SettingsController
{
    use BackendUserTrait;
    use LanguageServiceTrait;

    protected ModuleTemplate $moduleTemplate;

    protected ModuleInterface $currentModule;

    public array $MCONF = [];

    /**
     * The integer value of the GET/POST var, 'id'. Used for submodules to the 'Web' module (page id)
     *
     * @see init()
     */
    public int $id;

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly LanguagesService $languagesService,
    ) {
        $this->getLanguageService()
            ->includeLLFile('EXT:localizer/Resources/Private/Language/locallang_localizer_settings.xlf');
    }

    /**
     * Injects the request object for the current request or subrequest
     * Then checks for module functions that have hooked in, and renders menu etc.
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);

        $this->main();

        return $this->moduleTemplate->renderResponse('SettingsController/Index');
    }

    private function init(ServerRequestInterface $request): void
    {
        $this->currentModule = $request->getAttribute('module');
        $this->MCONF['name'] = $this->currentModule->getIdentifier();
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->id = (int)($request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0);
    }

    /**
     * @throws RouteNotFoundException
     */
    private function main(): void
    {
        $this->moduleTemplate->setTitle($this->getLanguageService()->sL('LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_settings.xlf:title'));

        if ($this->id > 0) {
            /** @var UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $listViewLink = $uriBuilder->buildUriFromRoute('web_list', ['id' => $this->id, 'table' => 'tx_localizer_settings']);
            $this->moduleTemplate->assign('listViewLink', $listViewLink);
        } else {
            $this->moduleTemplate->assign('listViewLink', false);
        }
    }

    public function populateLanguagesForTca(array &$fieldInformation): void
    {
        $allLanguages = $this->languagesService->getAll();

        if ($allLanguages !== []) {
            ksort($allLanguages);
            $fieldInformation['items'] = array_map(static function ($item) {
                return  [$item['label'], $item['value'], $item['icon']];
            }, $allLanguages);
            return;
        }

        // Fallback if no site configuration exists
        $recordPid = (int)($fieldInformation['row']['pid'] ?? 0);
        $languages = (new NullSite())->getAvailableLanguages($this->getBackendUser(), false, $recordPid);

        foreach ($languages as $languageId => $language) {
            $fieldInformation['items'][] = [
                'label' => $language->getTitle(),
                'value' => $languageId,
                'icon' => $language->getFlagIdentifier(),
            ];
        }
    }

    public function getDefaultLanguage(array &$fieldInformation): void
    {
        $recordPid = (int)($fieldInformation['row']['pid'] ?? 0);
        $defaultLanguage = $this->languagesService->getDefault($recordPid);

        $fieldInformation['items'][] = [
            'label' => $defaultLanguage->getTitle(),
            'value' => $defaultLanguage->getLanguageId(),
            'icon' => $defaultLanguage->getFlagIdentifier(),
        ];
    }
}
