<?php

namespace Localizationteam\Localizer\Controller;

use Exception;
use Localizationteam\Localizer\DatabaseConnection;
use Localizationteam\Localizer\Handler\FileExporter;
use Localizationteam\Localizer\Model\Repository\SelectorRepository;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * Module 'Selector' for the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 */
class SelectorController extends AbstractController
{
    /**
     * @var SelectorRepository
     */

    protected $selectorRepository;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var array
     */
    protected $languages = [];

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * @var array
     */
    protected $translatableTables = [];

    /**
     * @var int
     */
    protected $cartId;

    /**
     * @var string
     */
    protected $cshKey;

    /**
     * @var array
     */
    protected $storedTriples = [];

    /**
     * @var array
     */
    protected $legend = [];

    /**
     * @var array
     */
    protected $cartRecord = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var array
     */
    protected $tableHeaderSpan = [];

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'localizer_localizerselector';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->MCONF = [
            'name' => $this->moduleName,
        ];
        $this->selectorRepository = GeneralUtility::makeInstance(SelectorRepository::class);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->getBackendUser()->modAccess($this->MCONF, 1);
        $this->getLanguageService()->includeLLFile(
            'EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf'
        );
        $this->backPath = $GLOBALS['BACK_PATH'];
        $this->cshKey = '_MOD_' . $GLOBALS['MCONF']['name'];
    }

    /**
     * Initializing the module
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->configuration['languages'] = GeneralUtility::_GP('configured_languages') ? GeneralUtility::_GP(
            'configured_languages'
        ) : [];
        $this->configuration['tables'] = GeneralUtility::_GP('configured_tables') ? GeneralUtility::_GP(
            'configured_tables'
        ) : [];
        $this->configuration['start'] = GeneralUtility::_GP('configured_start') ? GeneralUtility::_GP(
            'configured_start'
        ) : 0;
        $this->configuration['end'] = GeneralUtility::_GP('configured_end') ? GeneralUtility::_GP('configured_end') : 0;

        if (GeneralUtility::_GP('selected_cart') === 'new') {
            $this->cartId = (int)$this->selectorRepository->createNewCart($this->id, $this->localizerId);
        } else {
            $this->cartId = (int)GeneralUtility::_GP('selected_cart');
        }
    }

    /**
     * Main function, starting the rendering of the list.
     *
     * @return void
     * @throws Exception
     */
    protected function main()
    {
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/DateTimePicker');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Localizer/LocalizerSelector');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/jquery.clearable');
        $this->moduleTemplate->getPageRenderer()->addCssFile(
            ExtensionManagementUtility::extPath('localizer') . 'Resources/Public/Css/localizer.css'
        );
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $access = is_array($this->pageinfo) ? 1 : 0;
        if ($access) {
            $this->modTSconfig['properties']['enableDisplayBigControlPanel'] = 'activated';
            if ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'activated') {
                $this->MOD_SETTINGS['bigControlPanel'] = true;
            } elseif ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'deactivated') {
                $this->MOD_SETTINGS['bigControlPanel'] = false;
            }
            if ($this->modTSconfig['properties']['enableClipBoard'] === 'activated') {
                $this->MOD_SETTINGS['clipBoard'] = true;
            } elseif ($this->modTSconfig['properties']['enableClipBoard'] === 'deactivated') {
                $this->MOD_SETTINGS['clipBoard'] = false;
            }
            if ($this->modTSconfig['properties']['enableLocalizationView'] === 'activated') {
                $this->MOD_SETTINGS['localization'] = true;
            } elseif ($this->modTSconfig['properties']['enableLocalizationView'] === 'deactivated') {
                $this->MOD_SETTINGS['localization'] = false;
            }
            /** @var DatabaseRecordList $dblist */
            $dblist = GeneralUtility::makeInstance('TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList');
            $dblist->backPath = $GLOBALS['BACK_PATH'];
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $dblist->script = $uriBuilder->buildUriFromRoute('web_list');
            $dblist->calcPerms = $this->getBackendUser()->calcPerms($this->pageinfo);
            $header = 'LOCALIZER Selector';
            if (isset($this->pageinfo['title'])) {
                $header .= ' : ';
            }
            $this->content = $this->moduleTemplate->header($header . $this->pageinfo['title']);
            $legendCells = '';
            if (!empty($this->legend)) {
                foreach ($this->legend as $legendItem) {
                    $label = $GLOBALS['LANG']->sL($legendItem['label']);
                    $legendCells .= '
                        <td class="' . $legendItem['cssClass'] . ' hover">
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
                <table class="table table-striped table-bordered table-hover">
                    <tr>' . $legendCells . '</tr>
                </table>
            </div>
            ';
            if ($this->id > 0) {
                $this->content .= '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" class="localizer_selector" id="localizer_selector">
                <input type="hidden" name="selected_localizer" value="' . $this->localizerId . '" />
                <input type="hidden" name="selected_localizerPid" value="' . $this->localizerPid . '" />
                <input type="hidden" name="selected_cart" value="' . $this->cartId . '" />
                <input type="hidden" name="id" value="' . $this->id . '" />';
                if ($this->cartId > 0 && empty(GeneralUtility::_GP('configuratorStore')) && empty(
                    GeneralUtility::_GP(
                        'configuratorFinalize'
                    )
                    )) {
                    $this->loadConfigurationAndCart();
                }
                if ($this->cartId > 0 && !empty(GeneralUtility::_GP('configuratorFinalize'))) {
                    $this->finalizeCart();
                    $this->exportConfiguredRecords();
                    $this->cartId = 0;
                }
                $this->content .= $this->getLocalizerConfigurator($dblist->listURL());
                if ($this->cartId > 0 && !empty(GeneralUtility::_GP('configuratorStore')) && empty(
                    GeneralUtility::_GP(
                        'configuratorFinalize'
                    )
                    )) {
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
                                $this->configuration
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
            $this->content .= '<div id="t3-modal-finalizecart" class="t3-modal t3-blr-modal t3-modal-finalizecart modal fade t3-modal-notice">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Please confirm cart finalization</h4>
                        </div>
                        <div class="modal-body">
                            <p>When you proceed, the cart can not be changed anymore and will be exported to be sent to the Localizer.</p>
                            <p>Press "Finalize" to proceed, otherwise press "Cancel".</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                            <a id="finalize-cart-submit" class="btn btn-success success">Finalize</a>
                        </div>
                    </div>
                </div>
            </div>';
        }
    }

    /**
     * Loads the configuration of the cart and the items that might already be in the cart
     */
    protected function loadConfigurationAndCart()
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
            $this->configuration['tables'] = array_merge($this->configuration['tables'], $storedTables);
        }
    }

    /**
     * Stores the configuration and selected items of the selected cart
     */
    protected function finalizeCart()
    {
        $this->storeConfigurationAndCart();
        $configurationId = $this->selectorRepository->storeL10nmgrConfiguration(
            $this->id,
            $this->localizerId,
            $this->cartId,
            $this->configuration
        );
        $this->selectorRepository->finalizeCart($this->localizerId, $this->cartId, $configurationId);
    }

    /**
     * Stores the configuration and selected items of the selected cart
     */
    protected function storeConfigurationAndCart()
    {
        $this->selectorRepository->storeConfiguration($this->id, $this->cartId, $this->configuration);
        $pageIds = [$this->id => $this->id];
        $this->selectorRepository->storeCart($pageIds, $this->cartId, $this->configuration, $this->storedTriples);
    }

    /**
     * Exports the records configured by the selector
     * @throws Exception
     */
    protected function exportConfiguredRecords()
    {
        /** @var FileExporter $fileExporter */
        $fileExporter = GeneralUtility::makeInstance(FileExporter::class);
        $fileExporter->init($this->cartId);
        $fileExporter->run();
    }

    /**
     * Generates the configurator for the selector matrix form
     *
     * @param $url
     * @return string
     */
    protected function getLocalizerConfigurator($url)
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
        if (!empty($this->configuration['languages']) && !empty($this->configuration['tables']) && empty(
            GeneralUtility::_GP(
                'configuratorFinalize'
            )
            )) {
            $localizerConfigurator .= '<li><button class="btn btn-success" type="button" 
                data-toggle="modal" data-target="#t3-modal-finalizecart">' .
                $GLOBALS['LANG']->sL(
                    'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:finalize'
                ) .
                '</button><input type="hidden" name="configuratorFinalize" id="configuratorFinalize" /></li>';
        }
        $localizerConfigurator .= '</ul></div>';
        return $localizerConfigurator;
    }

    /**
     * Generates the localizer selector for the configurator
     * which might be used to select one of the available localizer settings
     *
     * @param $url
     * @return string
     */
    protected function getLocalizerSelector($url)
    {
        if (count($this->availableLocalizers) === 1) {
            $this->localizerId = key($this->availableLocalizers);
        }
        $localizerSelector = '<li class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" id="localizerDropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">' .
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
                    <a href="' . $url . '&id=' . $this->id . '&selected_localizer=' . $id . '">' . $localizer['title'] . '</a>
                </li>';
            }
        }
        $localizerSelector .= '</ul>
            </li>';
        return $localizerSelector;
    }

    /**
     * Generates the cart selector for the configurator
     * which might be used to create a new cart or select an existing cart
     *
     * @param $url
     * @return string
     */
    protected function getCartSelector($url)
    {
        $availableCarts = $this->selectorRepository->loadAvailableCarts($this->localizerId);
        $cartSelector = '<li class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" id="localizerDropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">' .
            $GLOBALS['LANG']->sL(
                'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:cart.selector'
            ) .
            '<span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="localizerDropdownMenu2">
                <li><a href="' . $url . '&id=' . $this->id . '&selected_cart=new&selected_localizer=' . $this->localizerId . '&selected_localizerPid=' . $this->localizerPid . '">Create a new cart</a></li>
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
                    <a href="' . $url . '&id=' . $this->id . '&selected_cart=' . $id . '&selected_localizer=' . $this->localizerId . '">[' . $cart['uid'] . '] ' . $user . ' : ' . $date . '</a>
                </li>';
            }
        }
        $cartSelector .= '</ul>
            </li>';
        return $cartSelector;
    }

    /**
     * Generates the page selector for the configurator
     * which might be used to select a page to be working on within this cart
     *
     * @param $url
     * @return string
     */
    protected function getPageSelector($url)
    {
        $availablePages = $this->selectorRepository->loadAvailablePages($this->id, $this->cartId);
        if (empty($availablePages) || count($availablePages) === 1) {
            return '';
        }
        $pageSelector = '<li class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" id="localizerDropdownMenu3" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">' .
            $GLOBALS['LANG']->sL(
                'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:page.selector'
            ) .
            '<span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="localizerDropdownMenu3">';
        if (!empty($availablePages)) {
            foreach ($availablePages as $pid => $page) {
                $id = (int)$pid;
                $selected = '';
                if ($id === $this->id) {
                    $selected = ' class="active"';
                }
                $pageSelector .= '<li' . $selected . '>
                    <a href="' . $url . '&id=' . $id . '&selected_localizer=' . $this->localizerId . '&selected_cart=' . $this->cartId . '">[' . $page['pid'] . '] ' . $page['title'] . '</a>
                </li>';
            }
        }
        $pageSelector .= '</ul>
            </li>';
        return $pageSelector;
    }

    /**
     * Generates the language selector based on information collected during cart generation
     *
     * @return string
     */
    protected function getLanguageSelector()
    {
        $translationConfigurationProvider = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
        $systemLanguages = $translationConfigurationProvider->getSystemLanguages();
        $staticLanguages = $this->selectorRepository->getStaticLanguages($systemLanguages);
        $localizerLanguages = $this->selectorRepository->getLocalizerLanguages($this->localizerId);
        $targetLanguages = array_flip(GeneralUtility::intExplode(',', $localizerLanguages['target']));
        $availableLanguages = $this->selectorRepository->loadAvailableLanguages($this->cartId);
        $this->languages = [];
        $languages = [];
        if (!empty($staticLanguages)) {
            foreach ($staticLanguages as $language) {
                $languages[$language['title'] . '_' . $language['language_isocode'] . '_' . $language['uid']] = $language;
            }
            ksort($languages);
        }
        $languageSelector = '<li class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" id="localizerDropdownMenu4" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">' .
            $GLOBALS['LANG']->sL(
                'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:languages.selector'
            ) .
            '<span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="localizerDropdownMenu4">';
        if (count($languages) > 1) {
            $languageSelector .= '<li class="select-all"><a href="#" class="small" tabIndex="-1">
                        <input type="checkbox" />&nbsp;' . $this->getLanguageService()->sL(
                    'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:languages.selector.all'
                ) . '</a></li>';
        }
        if (!empty($languages)) {
            foreach ($languages as $language) {
                if ($language['uid'] > 0 &&
                    $this->getBackendUser()->checkLanguageAccess($language['uid'])
                    && isset($targetLanguages[$language['static_lang_isocode']])
                ) {
                    $checked = '';
                    if (isset($this->configuration['languages'][$language['uid']]) || isset($availableLanguages[$language['uid']])) {
                        $this->languages[$language['uid']] = $language;
                        $checked = ' checked="checked"';
                    }
                    $languageSelector .= '<li><a href="#" class="small" tabIndex="-1">
                            <input name="configured_languages[' . $language['uid'] . ']" type="checkbox" ' . $checked . ' />&nbsp;' .
                        $this->iconFactory->getIcon($language['flagIcon'], Icon::SIZE_SMALL) . ' ' .
                        $language['title'] . ' [' . $language['language_isocode'] . ']</a></li>';
                }
            }
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
     *
     * @return string
     */
    protected function getTableSelector()
    {
        $availableTables = $this->selectorRepository->loadAvailableTables($this->cartId);
        $tableSelector = '<li class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" id="localizerDropdownMenu5" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">' .
            $GLOBALS['LANG']->sL(
                'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:tables.selector'
            ) .
            '<span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="localizerDropdownMenu5">';
        $tables = array_keys($GLOBALS['TCA']);
        if (count($tables) > 1) {
            $tableSelector .= '<li class="select-all"><a href="#" class="small" tabIndex="-1">
                        <input type="checkbox" />&nbsp;' . $this->getLanguageService()->sL(
                    'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:tables.selector.all'
                ) . '</a></li>';
        }
        $tableSelector .= '<li><a href="#" class="small" tabIndex="-1">
            <input name="configured_tables[pages]-dummy" type="checkbox" checked="checked" disabled="disabled">
            <input name="configured_tables[pages]" type="hidden" value="1">&nbsp;' .
            $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['ctrl']['title']) . ' ' .
            $GLOBALS['LANG']->sL(
                'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:tables.selector.mandatory'
            ) .
            '</a></li>';
        $this->translatableTables = ['pages' => $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['ctrl']['title'])];
        foreach (array_keys($GLOBALS['TCA']) as $table) {
            if ($table === 'pages') {
                continue;
            }
            $recordExists = $this->getDatabaseConnection()
                ->exec_SELECTgetSingleRow(
                    '*',
                    $table,
                    'pid=' . (int)$this->id .
                    BackendUtility::BEenableFields($table) .
                    DatabaseConnection::deleteClause($table)
                );
            if ((!empty($recordExists) || isset($availableTables[$table])) &&
                BackendUtility::isTableLocalizable($table) &&
                ($this->getBackendUser()->isAdmin() ||
                    $this->getBackendUser()->check('tables_modify', $table))
            ) {
                $checked = '';
                if (isset($this->configuration['tables'][$table]) || isset($availableTables[$table])) {
                    $this->translatableTables[$table] = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
                    $checked = ' checked="checked"';
                }
                $tableSelector .= '<li><a href="#" class="small" tabIndex="-1"><input name="configured_tables[' . $table . ']" type="checkbox" ' . $checked . ' />&nbsp;' .
                    $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title']) . '</a></li>';
                if ($table === 'sys_file_reference') {
                    $checked = '';
                    if (isset($this->configuration['tables']['sys_file_metadata']) || isset($availableTables['sys_file_metadata'])) {
                        $this->translatableTables['sys_file_metadata'] = $GLOBALS['LANG']->sL($GLOBALS['TCA']['sys_file_metadata']['ctrl']['title']);
                        $checked = ' checked="checked"';
                    }
                    $tableSelector .= '<li><ul class="sys-file-metadata"><li><a href="#" class="small" tabIndex="-1">+&nbsp;<input name="configured_tables[sys_file_metadata]" type="checkbox" ' . $checked . ' />&nbsp;' .
                        $GLOBALS['LANG']->sL($GLOBALS['TCA']['sys_file_metadata']['ctrl']['title']) . '</a></li></ul></li>';
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
     *
     * @return string
     */
    protected function getTimeFrameSelector()
    {
        $timeFrameSelector = $this->getDateTimeSelector('start');
        $timeFrameSelector .= $this->getDateTimeSelector('end');
        return $timeFrameSelector;
    }

    /**
     * Generates the date time input fields with a datepicker
     *
     * @param $variableName
     * @return string
     */
    protected function getDateTimeSelector($variableName)
    {
        $value = '';
        if (!empty($this->configuration[$variableName])) {
            $value = ' value="' . $this->configuration[$variableName] . '"';
        }
        $dateTimeSelector = '<li class="input-group">';
        $dateTimeSelector .= '<input type="text" data-date-type="datetime" name="configured_' . $variableName . '" id="input-configured-' . $variableName . '"' . $value . ' class="t3js-datetimepicker form-control t3js-clearable" data-toggle="tooltip" data-placement="top" data-title="Pick ' . $variableName . ' date and time" />
        <label class="btn btn-default" for="input-configured-' . $variableName . '">
            <span class="t3js-icon icon icon-size-small icon-state-default icon-actions-edit-pick-date" data-identifier="actions-edit-pick-date">
                <span class="icon-markup">
                    <img src="/typo3/sysext/core/Resources/Public/Icons/T3Icons/actions/actions-edit-pick-date.svg" width="16" height="16">
                </span>
            </span>
        </label>';
        $dateTimeSelector .= '</li>';
        return $dateTimeSelector;
    }

    /**
     * Generates the actual translation matrix for available records of the selected tables and selected languages
     *
     * @return string
     */
    protected function getTranslationLocalizer()
    {
        $translationLocalizer = '';
        if (!empty($this->cartRecord)) {
            $id = (int)$this->cartRecord['uid'];
            $date = date('r', $this->cartRecord['crdate']);
            $user = $this->getBackendUser()->user['username'];
            $translationLocalizer = $this->moduleTemplate->header('[' . $id . '] ' . $user . ' : ' . $date);
        }
        $translationLocalizer .= '<div class="table-responsive localizer-selector-matrix"><table class="table table-striped table-bordered table-hover">';
        $translationLocalizer .= '<thead><tr><th>&#160;</th>';
        foreach ($this->languages as $languageId => $languageInfo) {
            $translationLocalizer .= '<th scope="col" class="text-center text-nowrap language-header column-hover">' .
                '<button class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" 
                    data-title="Select all records for ' . $languageInfo['title'] . '&nbsp;[' . $languageInfo['language_isocode'] . ']"
                >' . $this->iconFactory->getIcon($languageInfo['flagIcon'], Icon::SIZE_SMALL) . ' ' .
                $languageInfo['language_isocode'] . '</button></th>';
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
                        '" data-toggle="tooltip" data-placement="top" data-title="Select all languages for this record">' .
                        '<strong>' . $title . '</strong> : ' .
                        GeneralUtility::fixed_lgd_cs(
                            $record[$labelField],
                            50
                        ) . ' [' . $record['uid'] . ']</button></th>';
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
        $translationLocalizer .= '</tbody></table></div>';
        return $translationLocalizer;
    }

    /**
     * Generates a single matrix cell containing information about the table, the record, the language and the status
     *
     * @param $table
     * @param $uid
     * @param $placement
     * @return string
     */
    protected function generateLocalizerCells($table, $uid, $placement = 'top')
    {
        $cells = '';
        $GPvars = GeneralUtility::_GP('localizerSelectorCart');
        $tableVars = $GPvars[$table];
        foreach ($this->languages as $languageId => $languageInfo) {
            $checkBoxId = 'localizerSelectorCart[' . $table . '][' . $uid . '][' . $languageInfo['uid'] . ']';
            $identifier = md5($table . '.' . $uid . '.' . $languageInfo['uid']);
            $checked = $tableVars[$uid][$languageId] || $this->storedTriples[$identifier] ? ' checked=checked' : '';
            $title = $GLOBALS['LANG']->sL(
                $this->statusClasses[(int)$this->data['identifiedStatus'][$identifier]['status']]['label']
            );
            $status = $this->statusClasses[(int)$this->data['identifiedStatus'][$identifier]['status']]['cssClass'];
            $cells .= '<td class="' . $status . ' language-record-marker column-hover ' . (int)$this->data['identifiedStatus'][$identifier]['status'] . '">' .
                '<div class="btn-group" data-toggle="buttons">' .
                '<label data-toggle="tooltip" data-placement="' . $placement . '" data-title="' . $title . '" class="btn btn-' . $status . ($checked ? ' active' : '') . '">' .
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
     *
     * @param $referencedTable
     * @param $referencedUid
     * @param string $level
     * @param string $parents
     * @return string
     */
    protected function getReferenceLocalizer($referencedTable, $referencedUid, $level = '', $parents = '')
    {
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
                        '" data-toggle="tooltip" data-placement="top" data-title="Select all languages for this record">
                        <strong>' . $this->translatableTables[$table] . '</strong> : ' .
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