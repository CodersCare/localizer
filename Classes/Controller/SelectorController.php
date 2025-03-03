<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Controller;

use Exception;
use Localizationteam\Localizer\Handler\FileExporter;
use Localizationteam\Localizer\Model\Repository\AbstractRepository;
use Localizationteam\Localizer\Model\Repository\CartRepository;
use Localizationteam\Localizer\Model\Repository\LanguageRepository;
use Localizationteam\Localizer\Model\Repository\SelectorRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Module 'Selector' for the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
#[Controller]
class SelectorController extends AbstractController
{
    /** @var SiteLanguage[] */
    protected array $languages = [];

    protected array $configuration = [];

    protected array $translatableTables = [];

    protected int $cartId;

    protected string $cshKey;

    protected array $storedTriples = [];

    protected array $legend = [];

    protected array $cartRecord = [];

    protected array $data = [];

    protected int $tableHeaderSpan = 0;

    /**
     * @var null
     */
    protected $configuratorStore;

    /**
     * @var null
     */
    private $configuratorFinalize;

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        public readonly AbstractRepository $abstractRepository,
        public PageRenderer $pageRenderer,
        public readonly SelectorRepository $selectorRepository,
        public readonly IconFactory $iconFactory,
        public readonly LanguageRepository $languageRepository,
        public readonly CartRepository $cartRepository,
    ) {
        parent::__construct();

        $this->getLanguageService()->includeLLFile(
            'EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf'
        );
    }

    /**
     * Injects the request object for the current request or subrequest
     * Then checks for module functions that have hooked in, and renders menu etc.
     *
     * @throws Exception
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);

        $this->init($request);

        $this->main($request);

        //return $this->view->renderResponse('LocalizationModule/Index');
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Initializing the module
     */
    public function init(ServerRequestInterface $request): array
    {
        $localizer = parent::init($request);

        $this->currentModule = $request->getAttribute('module');
        $this->MCONF['name'] = $this->currentModule->getIdentifier();

        $this->configuration['languages'] = $request->getParsedBody()['configured_languages'] ?? $request->getQueryParams()['configured_languages'] ?? null ?: [];
        $this->configuration['tables'] = $request->getParsedBody()['configured_tables'] ?? $request->getQueryParams()['configured_tables'] ?? null ?: [];
        $this->configuration['start'] = $request->getParsedBody()['configured_start'] ?? $request->getQueryParams()['configured_start'] ?? null ?: 0;
        $this->configuration['end'] = $request->getParsedBody()['configured_end'] ?? $request->getQueryParams()['configured_end'] ?? null ?: 0;
        $this->configuration['deadline'] = $request->getParsedBody()['selected_deadline'] ?? $request->getQueryParams()['selected_deadline'] ?? null ?: '';
        $this->configuration['sortexports'] = (int)($localizer['sortexports'] ?? 0);
        $this->configuration['plainxmlexports'] = (bool)($localizer['plainxmlexports'] ?? false);

        $this->configuratorStore = $request->getParsedBody()['configuratorStore'] ?? $request->getQueryParams()['configuratorStore'] ?? null;
        $this->configuratorFinalize = $request->getParsedBody()['configuratorFinalize'] ?? $request->getQueryParams()['configuratorFinalize'] ?? null;

        if (($request->getParsedBody()['selected_cart'] ?? $request->getQueryParams()['selected_cart'] ?? null) === 'new') {
            $this->cartId = $this->selectorRepository->createNewCart($this->id, $this->localizerId);
        } else {
            $this->cartId = (int)($request->getParsedBody()['selected_cart'] ?? $request->getQueryParams()['selected_cart'] ?? null);
        }
        return [];
    }

    /**
     * Main function, starting the rendering of the list.
     *
     * @throws Exception
     */
    protected function main(ServerRequestInterface $request): void
    {
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/DateTimePicker');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Localizer/LocalizerSelector');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Input/Clearable');
        $this->pageRenderer->addCssFile(
            ExtensionManagementUtility::extPath('localizer') . 'Resources/Public/Css/localizer.css'
        );

        $header = 'LOCALIZER Selector';
        $this->moduleTemplate->setTitle($header);

        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);

        if (isset($this->pageinfo['title'])) {
            $header .= ': ';
        }
        $this->content = $this->moduleTemplate->header($header . ($this->pageinfo['title'] ?? ''));
        $legendCells = '';
        if (!empty($this->legend)) {
            foreach ($this->legend as $legendItem) {
                $label = $GLOBALS['LANG']->sL($legendItem['label']);
                $legendCells .= '
                    <td class="' . $legendItem['cssClass'] . ' legend-item hover">
                        <div class="btn-group" data-toggle="buttons">
                            <label class="btn btn-' . $legendItem['cssClass'] . ' localizer-legend">
                                <input type="checkbox" disabled="disabled">' . $label . '
                            </label>&nbsp;<label class="btn btn-' . $legendItem['cssClass'] . ' active">
                            <input type="checkbox" disabled="disabled">' . $GLOBALS['LANG']->sL(
                    'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:legend.cart'
                ) . '
                        </label>
                        </div>
                    </td>
                ';
            }
        }

        $this->content .= '
        <div class="table-responsive localizer-matrix-configurator">
            <table class="table table-striped table-bordered">
                <tr>' . $legendCells . '</tr>
            </table>
        </div>
        ';

        if ($this->id > 0) {
            $this->content .= '<form action="' . htmlspecialchars($this->formUrl()) .
                '" method="post" class="localizer_selector" id="localizer_selector">
            <input type="hidden" name="selected_deadline" value="0" />
            <input type="hidden" name="selected_localizer" value="' . $this->localizerId . '" />
            <input type="hidden" name="selected_localizerPid" value="' . $this->localizerPid . '" />
            <input type="hidden" name="selected_cart" value="' . $this->cartId . '" />
            <input type="hidden" name="id" value="' . $this->id . '" />';
            if ($this->cartId > 0 && empty(GeneralUtility::_GP('configuratorStore'))
                && empty(GeneralUtility::_GP('configuratorFinalize'))
            ) {
                $this->loadConfigurationAndCart();
            }
            if ($this->cartId > 0 && !empty(GeneralUtility::_GP('configuratorFinalize'))) {
                $this->finalizeCart();
                $this->exportConfiguredRecords();
                $this->cartId = 0;
            }

            $this->content .= $this->getLocalizerConfigurator($this->formUrl());
            if ($this->cartId > 0 && !empty(GeneralUtility::_GP('configuratorStore'))
                && empty(GeneralUtility::_GP('configuratorFinalize'))
            ) {

                $this->storeConfigurationAndCart();
            }

            if ($this->localizerId) {
                if ($this->cartId) {
                    if (empty($this->configuration)) {
                        $this->content .= '<div class="alert alert-warning">' .
                            $GLOBALS['LANG']->sL(
                                'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:cart.configure'
                            ) .
                            '</div>';
                    } else {
                        $pageIds = $this->selectorRepository->loadAvailablePages($this->id, 0);
                        $this->data = $this->selectorRepository->getRecordsOnPages(
                            $this->id,
                            $pageIds,
                            $this->translatableTables,
                            $this->configuration,
                            array_keys($this->languages)
                        );
                        $this->content .= $this->getTranslationLocalizer();
                    }
                } elseif (!empty(GeneralUtility::_GP('configuratorFinalize'))) {
                    $this->content .= '<div class="alert alert-success">' .
                        $GLOBALS['LANG']->sL(
                            'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:finalize.success'
                        ) .
                        '</div>';
                } else {
                    $this->content .= '<div class="alert alert-warning">' .
                        $GLOBALS['LANG']->sL(
                            'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:cart.select'
                        ) .
                        '</div>';
                }
            } else {
                $this->content .= '<div class="alert alert-warning">' .
                    $GLOBALS['LANG']->sL(
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:localizer.select'
                    ) .
                    '</div>';
            }
        } else {
            $this->content .= '<div class="alert alert-warning">' .
                $GLOBALS['LANG']->sL(
                    'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:page.select'
                ) .
                '</div>';
        }
        $this->content .= '</form>';

        // @todo Use TYPO3 Modal API for this.
        $this->content .= '<div id="t3-modal-finalizecart" class="modal-size-medium t3-modal t3-blr-modal t3-modal-finalizecart modal fade t3-modal-notice">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Please confirm cart finalization</h4>
                    </div>
                    <div class="modal-body">
                        <p>When you proceed, the cart can not be changed anymore and will be exported to be sent to the Localizer.</p>';
        if ($this->availableLocalizers[$this->localizerId]['deadline'] ?? false) {
            $this->content .= '
                        <p>If necessary pick a deadline for this job here: </p>
                        <ul class="list-inline">' .
                $this->getDateTimeSelector('configured_deadline') .
                '</ul>';
        }
        $this->content .= '
                        <p>Press "Finalize" to proceed, otherwise press "Cancel".</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                        <a id="finalize-cart-submit" class="btn btn-success success">Finalize</a>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * A URL pointing to this module used for the form action attribute.
     */
    protected function formUrl(): string
    {
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute($this->currentModule->getIdentifier());
    }

    /**
     * Loads the configuration of the cart and the items that might already be in the cart
     */
    protected function loadConfigurationAndCart(): void
    {
        $this->configuration = $this->selectorRepository->loadConfiguration($this->cartId);
        $pageIds = $this->selectorRepository->loadAvailablePages($this->id, $this->cartId);
        $this->storedTriples = $this->selectorRepository->loadStoredTriples($pageIds, $this->cartId);
        if (!empty($this->storedTriples)) {
            $storedTables = [];
            foreach ($this->storedTriples as $triple) {
                $storedTables[$triple['tablename']] = 'on';
            }
        }
        if (!empty($storedTables)) {
            $this->configuration['tables'] = array_merge((array)($this->configuration['tables'] ?? []), $storedTables);
        }
    }

    /**
     * Stores the configuration and selected items of the selected cart
     */
    protected function finalizeCart(): void
    {
        $this->storeConfigurationAndCart();
        $configurationId = $this->selectorRepository->storeL10nmgrConfiguration(
            $this->id,
            $this->localizerId,
            $this->cartId,
            $this->configuration
        );
        $this->selectorRepository->finalizeCart(
            $this->localizerId,
            $this->cartId,
            $configurationId,
            $this->configuration['deadline']
        );
    }

    /**
     * Stores the configuration and selected items of the selected cart
     */
    protected function storeConfigurationAndCart(): void
    {
        $this->selectorRepository->storeConfiguration($this->id, $this->cartId, $this->configuration);
        $pageIds = [$this->id => $this->id];
        $this->selectorRepository->storeCart($pageIds, $this->cartId, $this->configuration, $this->storedTriples);
    }

    /**
     * Exports the records configured by the selector
     * @throws Exception
     */
    protected function exportConfiguredRecords(): void
    {
        /** @var FileExporter $fileExporter */
        $fileExporter = GeneralUtility::makeInstance(FileExporter::class);
        $fileExporter->init($this->cartId);
        $fileExporter->run();
    }

    /**
     * Generates the configurator for the selector matrix form
     */
    protected function getLocalizerConfigurator(string $url): string
    {
        $localizerConfigurator = '<div class="localizer-matrix-configurator"><ul class="list-inline">';
        $localizerConfigurator .= $this->getLocalizerSelector($url);
        if ($this->localizerId) {
            $localizerConfigurator .= $this->getCartSelector($url);
        }
        if ($this->cartId) {
            $localizerConfigurator .= $this->getPageSelector($url);
            $localizerConfigurator .= $this->getLanguageSelector();
            $localizerConfigurator .= $this->getTableSelector();
            $localizerConfigurator .= $this->getTimeFrameSelector();
            $localizerConfigurator .= '<li><button class="btn btn-info" name="configuratorStore" type="submit" value="store">' .
                $GLOBALS['LANG']->sL(
                    'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:store'
                ) .
                '</button></li>';
        }
        if (!empty($this->configuration['languages']) && !empty($this->configuration['tables'])
            && empty(GeneralUtility::_GP('configuratorFinalize'))
        ) {
            $localizerConfigurator .= '<li><button class="btn btn-success" type="button"
                data-toggle="modal" data-bs-toggle="modal" data-target="#t3-modal-finalizecart" data-bs-target="#t3-modal-finalizecart">' .
                $GLOBALS['LANG']->sL(
                    'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:finalize'
                ) .
                '</button><input type="hidden" name="configuratorFinalize" id="configuratorFinalize" /></li>';
        }
        return $localizerConfigurator . '</ul></div>';
    }

    /**
     * Generates the localizer selector for the configurator
     * which might be used to select one of the available localizer settings
     */
    protected function getLocalizerSelector(string $url): string
    {
        if (count($this->availableLocalizers) === 1) {
            $this->localizerId = key($this->availableLocalizers);
        }
        $localizerSelector = '<li class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" id="localizerDropdownMenu1" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' .
            $GLOBALS['LANG']->sL(
                'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:localizer.selector'
            ) .
            '<span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="localizerDropdownMenu1">';
        if (!empty($this->availableLocalizers)) {
            foreach ($this->availableLocalizers as $uid => $localizer) {
                $id = (int)$uid;
                $selected = '';
                if ($id === $this->localizerId) {
                    $selected = ' class="active"';
                }
                $localizerSelector .= '<li' . $selected . '>
                    <a class="dropdown-item" href="' . $url . '&id=' . $this->id . '&selected_localizer=' . $id . '">' . $localizer['title'] . '</a>
                </li>';
            }
        }
        return $localizerSelector . '</ul>
            </li>';
    }

    /**
     * Generates the cart selector for the configurator
     * which might be used to create a new cart or select an existing cart
     */
    protected function getCartSelector(string $url): string
    {
        $availableCarts = $this->selectorRepository->loadAvailableCarts($this->localizerId);
        $cartSelector = '<li class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" id="localizerDropdownMenu2" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' .
            $GLOBALS['LANG']->sL(
                'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:cart.selector'
            ) .
            '<span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="localizerDropdownMenu2">
                <li><a class="dropdown-item" href="' . $url . '&id=' . $this->id . '&selected_cart=new&selected_localizer=' . $this->localizerId . '&selected_localizerPid=' . $this->localizerPid . '">Create a new cart</a></li>
                <li role="separator" class="divider"></li>';
        if (!empty($availableCarts)) {
            foreach ($availableCarts as $cart) {
                $id = (int)$cart['uid'];
                $date = date('r', $cart['crdate']);
                $user = $this->getBackendUser()->user['username'];
                $selected = '';
                if ($id === $this->cartId) {
                    $selected = ' class="active"';
                    $this->cartRecord = $cart;
                }
                $cartSelector .= '<li' . $selected . '>
                    <a class="dropdown-item" href="' . $url . '&id=' . $this->id . '&selected_cart=' . $id . '&selected_localizer=' . $this->localizerId . '">[' . $cart['uid'] . '] ' . $user . ': ' . $date . '</a>
                </li>';
            }
        }

        return $cartSelector . '</ul>
            </li>';
    }

    /**
     * Generates the page selector for the configurator
     * which might be used to select a page to be working on within this cart
     */
    protected function getPageSelector(string $url): string
    {
        $availablePages = $this->selectorRepository->loadAvailablePages($this->id, $this->cartId);

        if (empty($availablePages) || count($availablePages) === 1) {
            return '';
        }

        $pageSelector = '<li class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" id="localizerDropdownMenu3" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' .
            $GLOBALS['LANG']->sL(
                'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:page.selector'
            ) .
            '<span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="localizerDropdownMenu3">';

        foreach ($availablePages as $pid => $page) {
            $id = (int)$pid;
            $selected = '';
            if ($id === $this->id) {
                $selected = ' class="active"';
            }
            $pageSelector .= '<li' . $selected . '>
                <a class="dropdown-item" href="' . $url . '&id=' . $id . '&selected_localizer=' . $this->localizerId . '&selected_cart=' . $this->cartId . '">[' . $page['pid'] . '] ' . $page['title'] . '</a>
            </li>';
        }

        return $pageSelector . '</ul>
            </li>';
    }

    /**
     * Generates the language selector based on information collected during cart generation
     *
     * @throws SiteNotFoundException
     */
    protected function getLanguageSelector(): string
    {
        $staticLanguages = $this->languageRepository->getStaticLanguages($this->id);
        $localizerLanguages = $this->selectorRepository->getLocalizerLanguages($this->localizerId);
        array_flip(GeneralUtility::intExplode(',', $localizerLanguages['target']));
        $availableLanguages = $this->selectorRepository->loadAvailableLanguages($this->cartId);
        $this->languages = [];
        $languages = [];

        foreach ($staticLanguages as $language) {
             $key = sprintf('%s_%s_%s', $language->getTitle(), $language->getLocale()->getLanguageCode(), $language->getLanguageId());
             $languages[$key] = $language;
         }
         ksort($languages);

        $languageSelector = '<li class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" id="localizerDropdownMenu4" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' .
            $GLOBALS['LANG']->sL(
                'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:languages.selector'
            ) .
            '<span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="localizerDropdownMenu4">';

        if (count($languages) > 1) {
            $languageSelector .= '<li class="select-all"><a href="#" class="small dropdown-item" tabIndex="-1">
                        <input type="checkbox" />&nbsp;' . $this->getLanguageService()->sL(
                'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:languages.selector.all'
            ) . '</a></li>';
        }
        foreach ($languages as $language) {
            if ($language->getLanguageId() === 0) {
               continue;
            }
            if (isset($this->configuration['languages'][$language->getLanguageId()]) || isset($availableLanguages[$language->getLanguageId()])) {
                $this->languages[$language->getLanguageId()] = $language;
                $checked = ' checked="checked"';
            }
            $languageSelector .= '<li><a href="#" class="small dropdown-item" tabIndex="-1">
                    <input name="configured_languages[' . $language->getLanguageId() . ']" type="checkbox" ' . $checked . ' />&nbsp;' .
                $this->iconFactory->getIcon($language->getFlagIdentifier(), Icon::SIZE_SMALL) . ' ' .
                $language->getTitle() . ' [' . $language->getLocale()->getLanguageCode() . ']</a></li>';
        }
        $languageSelector .= '</ul>
        </li>';

        if (!empty($this->configuration['languages'])) {
            foreach ($this->configuration['languages'] as $key => $language) {
                if (!isset($this->languages[(int)$key])) {
                    unset($this->configuration['languages'][(int)$key]);
                }
            }
        }

        return $languageSelector;
    }

    /**
     * Generates the table selector based on information collected during cart generation
     */
    protected function getTableSelector(): string
    {
        $availableTables = $this->selectorRepository->loadAvailableTables($this->cartId);

        $tableSelector = '<li class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" id="localizerDropdownMenu5" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' .
            $GLOBALS['LANG']->sL(
                'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:tables.selector'
            ) .
            '<span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="localizerDropdownMenu5">';

        $tables = array_keys($GLOBALS['TCA']);

        if (count($tables) > 1) {
            $tableSelector .= '<li class="select-all"><a href="#" class="small dropdown-item" tabIndex="-1">
                        <input type="checkbox" />&nbsp;' . $this->getLanguageService()->sL(
                'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:tables.selector.all'
            ) . '</a></li>';
        }

        $tableSelector .= '<li><a href="#" class="small dropdown-item" tabIndex="-1">
            <input name="configured_tables[pages]-dummy" type="checkbox" checked="checked" disabled="disabled">
            <input name="configured_tables[pages]" type="hidden" value="1">&nbsp;' .
            $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['ctrl']['title']) . ' ' .
            $GLOBALS['LANG']->sL(
                'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:tables.selector.mandatory'
            ) .
            '</a></li>';

        $this->translatableTables = ['pages' => $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['ctrl']['title'])];

        // TODO: Maybe we can use AutomaticExporter::findTranslatableTables($this->id)
        foreach (array_keys($GLOBALS['TCA']) as $table) {
            if ($table === 'pages') {
                continue;
            }

            if (BackendUtility::isTableLocalizable($table)) {
                $recordExists = $this->selectorRepository->checkForRecordsOnPage($this->id, $table);
                if ((!empty($recordExists) || isset($availableTables[$table])) &&
                    ($this->getBackendUser()->isAdmin() || $this->getBackendUser()->check('tables_modify', $table))
                ) {
                    $checked = '';
                    if (isset($this->configuration['tables'][$table]) || isset($availableTables[$table])) {
                        $this->translatableTables[$table] = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
                        $checked = ' checked="checked"';
                    }
                    $tableSelector .= '<li><a href="#" class="small dropdown-item" tabIndex="-1"><input name="configured_tables[' . $table . ']" type="checkbox" ' . $checked . ' />&nbsp;' .
                        $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title']) . '</a></li>';
                    if ($table === 'sys_file_reference') {
                        $checked = '';
                        if (isset($this->configuration['tables']['sys_file_metadata']) || isset($availableTables['sys_file_metadata'])) {
                            $this->translatableTables['sys_file_metadata'] = $GLOBALS['LANG']->sL($GLOBALS['TCA']['sys_file_metadata']['ctrl']['title']);
                            $checked = ' checked="checked"';
                        }
                        $tableSelector .= '<li><ul class="sys-file-metadata"><li><a href="#" class="small dropdown-item" tabIndex="-1">+&nbsp;<input name="configured_tables[sys_file_metadata]" type="checkbox" ' . $checked . ' />&nbsp;' .
                            $GLOBALS['LANG']->sL($GLOBALS['TCA']['sys_file_metadata']['ctrl']['title']) . '</a></li></ul></li>';
                    }
                }
            }
        }
        $tableSelector .= '</ul>
        </li>';

        if (!empty($this->configuration['tables'])) {
            foreach ($this->configuration['tables'] as $table) {
                if (!isset($GLOBALS['TCA'][$table])) {
                    unset($this->configuration['tables'][$table]);
                }
            }
        }
        return $tableSelector;
    }

    /**
     * Generates the time frame selector based on information collected during cart generation
     */
    protected function getTimeFrameSelector(): string
    {
        $timeFrameSelector = $this->getDateTimeSelector('start');
        return $timeFrameSelector . $this->getDateTimeSelector('end');
    }

    /**
     * Generates the date time input fields with a datepicker
     *
     * @param $variableName
     */
    protected function getDateTimeSelector($variableName): string
    {
        $value = '';
        if (!empty($this->configuration[$variableName])) {
            $value = ' value="' . $this->configuration[$variableName] . '"';
        }
        $icon = $this->iconFactory->getIcon('actions-calendar', Icon::SIZE_SMALL)->render();
        $dateTimeSelector = '<li class="input-group">';
        $dateTimeSelector .= '<input type="text" data-date-type="datetime" name="configured_' . $variableName . '" id="input-configured-' . $variableName . '"' . $value . ' class="t3js-datetimepicker form-control t3js-clearable" data-bs-toggle="tooltip"  data-toggle="tooltip" data-placement="top"  title="Pick ' . $variableName . ' date and time" />';
        $dateTimeSelector .= '<label class="btn btn-default" for="input-configured-' . $variableName . '">' . $icon . '</label>';
        return $dateTimeSelector . '</li>';
    }

    /**
     * Generates the actual translation matrix for available records of the selected tables and selected languages
     */
    protected function getTranslationLocalizer(): string
    {
        $translationLocalizer = '';
        if (!empty($this->cartRecord)) {
            $id = (int)$this->cartRecord['uid'];
            $date = date('r', $this->cartRecord['crdate']);
            $user = $this->getBackendUser()->user['username'];
            $translationLocalizer = $this->moduleTemplate->header('[' . $id . '] ' . $user . ': ' . $date);
        }
        $translationLocalizer .= '<div class="table-responsive localizer-selector-matrix"><table class="table table-striped table-bordered table-hover">';
        $translationLocalizer .= '<thead><tr><th>&#160;</th>';
        foreach ($this->languages as $languageId => $language) {
            $translationLocalizer .= '<th scope="col" class="text-center text-nowrap language-header column-hover">' .
                '<button class="btn btn-default btn-sm" data-bs-toggle="tooltip"  data-toggle="tooltip" data-placement="top"
                     title="Select all records for ' . $language->getTitle() . '&nbsp;[' . $language->getLocale()->getLanguageCode() . ']"
                >' . $this->iconFactory->getIcon($language->getFlagIdentifier(), Icon::SIZE_SMALL) . ' ' .
                $language->getLocale()->getLanguageCode() . '</button></th>';
        }
        $translationLocalizer .= '</tr></thead><tbody>';
        $this->tableHeaderSpan = count($this->languages) + 2;
        foreach ($this->translatableTables as $table => $title) {
            if ($table === 'pages') {
                continue;
            }
            $labelField = $GLOBALS['TCA'][$table]['ctrl']['label'];
            if (!empty($this->data['records'][$table])) {
                $counter = 0;
                foreach ($this->data['records'][$table] as $uid => $record) {
                    $placement = $counter === 0 ? 'bottom' : 'top';
                    $translationLocalizer .= '<tr class="parent-' . $table . '-' . $uid . '"><th class="active text-nowrap record-header">' .
                        '<button class="btn btn-default btn-sm" data-tableid="' . $table . '-' . $uid .
                        '" data-bs-toggle="tooltip"  data-toggle="tooltip" data-placement="top"  title="Select all languages for this record">' .
                        '<strong>' . $title . '</strong>: ' .
                        GeneralUtility::fixed_lgd_cs((string) $record[$labelField], 50) . ' [' . $record['uid'] . ']</button></th>';
                    $translationLocalizer .= $this->generateLocalizerCells($table, $uid, $placement);
                    $translationLocalizer .= '</tr>';
                    $translationLocalizer .= $this->getReferenceLocalizer(
                        $table,
                        $uid,
                        '',
                        'parent-' . $table . '-' . $uid
                    );
                    $counter++;
                }
            }
        }
        return $translationLocalizer . '</tbody></table></div>';
    }

    /**
     * Generates a single matrix cell containing information about the table, the record, the language and the status
     */
    protected function generateLocalizerCells(string $table, int $uid, string $placement = 'top'): string
    {
        $cells = '';
        $GPvars = GeneralUtility::_GP('localizerSelectorCart');
        $tableVars = $GPvars[$table] ?? [];
        foreach ($this->languages as $languageId => $language) {
            $checkBoxId = 'localizerSelectorCart[' . $table . '][' . $uid . '][' . $language->getLanguageId() . ']';
            $identifier = md5($table . '.' . $uid . '.' . $language->getLanguageId());
            $checked = ($tableVars[$uid][$languageId] ?? false) || ($this->storedTriples[$identifier] ?? false) ? ' checked=checked' : '';
            $identifiedStatus = (int)($this->data['identifiedStatus'][$identifier]['status'] ?? 0);
            $title = $GLOBALS['LANG']->sL(
                $this->statusClasses[$identifiedStatus]['label']
            );
            $status = $this->statusClasses[$identifiedStatus]['cssClass'];
            $cells .= '<td class="' . $status . ' language-record-marker column-hover ' . $identifiedStatus . '">' .
                '<div class="btn-group" data-toggle="buttons">' .
                '<label data-bs-toggle="tooltip"  data-toggle="tooltip" data-placement="' . $placement . '"  title="' . $title . '" class="btn btn-' . $status . ($checked ? ' active' : '') . '">' .
                '<input type="checkbox" name="' . $checkBoxId . '" autocomplete="off"' . $checked . ' />&#160;' .
                '</label>' .
                '</div>' .
                '</td>';
        }
        return $cells;
    }

    /**
     * Generates the translation matrix for referenced records that are children of records in the main matrix
     * and puts them into a tree structure below their parent element
     */
    protected function getReferenceLocalizer(
        string $referencedTable,
        int $referencedUid,
        string $level = '',
        string $parents = ''
    ): string {
        $referenceLocalizer = '';
        if (!empty($this->data['referencedRecords'][$referencedTable][$referencedUid])) {
            foreach ($this->data['referencedRecords'][$referencedTable][$referencedUid] as $table => $referencedRecords) {
                ksort($referencedRecords);
                $labelField = $GLOBALS['TCA'][$table]['ctrl']['label'];
                $counter = 0;
                $recordCount = count($referencedRecords);
                foreach ($referencedRecords as $record) {
                    $counter++;
                    if ($counter === $recordCount) {
                        $treeNodes = '&boxur;';
                        $treeAdd = '&nbsp;&nbsp;';
                    } else {
                        $treeNodes = '&boxvr;';
                        $treeAdd = '&boxv;';
                    }
                    $referenceLocalizer .= '<tr class="' . $parents . ' parent-' . $table . '-' . $record['uid'] . '">
                    <td class="text-nowrap record-header">' . $level . $treeNodes .
                        '<button class="btn btn-default btn-sm" data-tableid="' . $table . '-' . $record['uid'] .
                        '" data-bs-toggle="tooltip"  data-toggle="tooltip" data-placement="top"  title="Select all languages for this record">
                        <strong>' . $this->translatableTables[$table] . '</strong>: ' .
                        GeneralUtility::fixed_lgd_cs(
                            $record[$labelField],
                            50
                        ) . ' [' . $record['uid'] . ']</button></td>';
                    $referenceLocalizer .= $this->generateLocalizerCells($table, $record['uid']);
                    $referenceLocalizer .= '</tr>';
                    $referenceLocalizer .= $this->getReferenceLocalizer(
                        $table,
                        $record['uid'],
                        $level . $treeAdd,
                        $parents . ' parent-' . $table . '-' . $record['uid']
                    );
                }
                unset($this->data['referencedRecords'][$referencedTable][$referencedUid][$table]);
            }
        }
        return $referenceLocalizer;
    }
}
