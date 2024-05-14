<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Controller;

use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Model\Repository\AbstractRepository;
use Localizationteam\Localizer\Model\Repository\CartRepository;
use Localizationteam\Localizer\Traits\BackendUserTrait;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Module 'Cart' for the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
#[Controller]
class CartController extends AbstractController
{
    use BackendUserTrait;

    protected int $pointer;

    protected string $imagemode;

    protected string $table;

    protected string $search_field;

    protected int $search_levels;

    protected int $showLimit;

    protected string $returnUrl;

    protected array $cmd;

    protected string $cmd_table;

    protected int $userId;

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        public readonly AbstractRepository $abstractRepository,
        public PageRenderer $pageRenderer,
        public readonly CartRepository $cartRepository,
        public readonly Typo3Version $typo3Version,
    ) {
        $this->getLanguageService()->includeLLFile(
            'EXT:localizer/Resources/Private/Language/locallang_localizer_cart.xlf'
        );
    }

    /**
     * Initialize function menu array
     *
     * @todo Define visibility
     */
    public function menuConfig(): void
    {
        $this->MOD_MENU = [
            'bigControlPanel' => '',
            'clipBoard' => '',
            'localization' => '',
        ];
    }

    /**
     * Initializing the module
     */
    public function init(ServerRequestInterface $request): array
    {
        parent::init($request);

        $this->userId = (int)($request->getParsedBody()['selected_user'] ?? $request->getQueryParams()['selected_user'] ?? null);
        $this->pointer = (int)($request->getParsedBody()['pointer'] ?? $request->getQueryParams()['pointer'] ?? null);
        $this->imagemode = (string)($request->getParsedBody()['imagemode'] ?? $request->getQueryParams()['imagemode'] ?? null);
        $_GET['table'] = Constants::TABLE_LOCALIZER_CART;
        $this->table = (string)($request->getParsedBody()['table'] ?? $request->getQueryParams()['table'] ?? null);
        $this->search_field = (string)($request->getParsedBody()['search_field'] ?? $request->getQueryParams()['search_field'] ?? null);
        $this->search_levels = (int)($request->getParsedBody()['search_levels'] ?? $request->getQueryParams()['search_levels'] ?? null);
        $this->showLimit = (int)($request->getParsedBody()['showLimit'] ?? $request->getQueryParams()['showLimit'] ?? null);
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl((string)($request->getParsedBody()['returnUrl'] ?? $request->getQueryParams()['returnUrl'] ?? null));
        $this->cmd = (array)($request->getParsedBody()['cmd'] ?? $request->getQueryParams()['cmd'] ?? null);
        $this->cmd_table = (string)($request->getParsedBody()['cmd_table'] ?? $request->getQueryParams()['cmd_table'] ?? null);
        return [];
    }

    /**
     * Main function, starting the rendering of the list.
     */
    protected function main(ServerRequestInterface $request): void
    {
        $this->pageRenderer->loadJavaScriptModule('TYPO3/CMS/Backend/Tooltip');
        $this->pageRenderer->loadJavaScriptModule('TYPO3/CMS/Localizer/LocalizerCart');

        $this->pageRenderer->addCssFile(
            ExtensionManagementUtility::extPath('localizer') . 'Resources/Public/Css/localizer.css'
        );
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $access = is_array($this->pageinfo) ? 1 : 0;
        $this->MOD_SETTINGS['bigControlPanel'] = true;
        $this->MOD_SETTINGS['clipBoard'] = false;
        $this->MOD_SETTINGS['localization'] = false;
        $permissionBits = $this->getBackendUser()->calcPerms($this->pageinfo);

        /** @var \TYPO3\CMS\Backend\RecordList\DatabaseRecordList $dblist */
        $dblist = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\RecordList\DatabaseRecordList::class);
        $dblist->setRequest($request);
        $dblist->calcPerms = new Permission($permissionBits);
        $dblist->returnUrl = $this->returnUrl;
        $dblist->disableSingleTableView = $this->modTSconfig['properties']['disableSingleTableView'] ?? false;
        $dblist->listOnlyInSingleTableMode = $this->modTSconfig['properties']['listOnlyInSingleTableView'] ?? false;
        $dblist->hideTables = $this->modTSconfig['properties']['hideTables'] ?? false;
        $dblist->hideTranslations = $this->modTSconfig['properties']['hideTranslations'] ?? '';
        $dblist->tableTSconfigOverTCA = $this->modTSconfig['properties']['table.'] ?? [];

        if ($this->typo3Version->getMajorVersion() < 12) {
            $dblist->allowedNewTables = [];
            $dblist->clickTitleMode = '';
        }

        $dblist->deniedNewTables = [Constants::TABLE_LOCALIZER_CART];
        $dblist->setIsEditable(true);
        $dblist->pageRow = $this->pageinfo;
        $dblist->modTSconfig = $this->modTSconfig;

        $header = 'LOCALIZER Cart';
        $this->content = $this->moduleTemplate->header($header);
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
                <table class="table table-striped table-bordered table-hover">
                    <tr>' . $legendCells . '</tr>
                </table>
            </div>
            ';

        $this->content .= '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">';
        $this->content .= $this->getCartConfigurator($dblist->listURL());
        $HTMLcode = '';

        if ($access || ($this->id === 0 && $this->search_levels > 0 && strlen($this->search_field) > 0)) {
            $this->pointer = MathUtility::forceIntegerInRange($this->pointer, 0, 100000);
            $dblist->start($this->id, $this->table, $this->pointer, $this->search_field, 999, $this->showLimit);

            if ($this->typo3Version->getMajorVersion() < 12) {
                $dblist->setDispFields();
            }

            $HTMLcode = $dblist->generateList();

            if ($this->typo3Version->getMajorVersion() < 12) {
                $this->moduleTemplate->addJavaScriptCode(
                    'lcoalizer_cart_record_info',
                    $this->generateRecordInfo()
                );
            }
        }
        $HTMLcode = property_exists($dblist, 'HTMLcode') ? $dblist->HTMLcode : $HTMLcode;
        if ($this->localizerId) {
            $this->content .= $HTMLcode;
        } else {
            $this->content .= '<div class="alert alert-warning">' .
                $GLOBALS['LANG']->sL(
                    'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_cart.xlf:localizer.select'
                ) .
                '</div>';
        }
        $this->content .= '<input type="hidden" name="selected_localizer" value="' . $this->localizerId . '" />
            <input type="hidden" name="selected_localizerPid" value="' . $this->localizerPid . '" />';
        $this->content .= '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';
        if ($HTMLcode) {
            $this->content .= '
                    </form>
                </div>';
        } elseif ($this->localizerId) {
            $this->content .= '<div class="error">No cart items found for Localizer in this rootline</div>';
        }
        $this->content .= '<div id="t3-modal-importscheduled" class="t3-modal t3-blr-modal t3-modal-importscheduled modal fade t3-modal-notice">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Import has been scheduled</h4>
                    </div>
                    <div class="modal-body">
                        <p>The translation import has been scheduled and will be executed with the next scheduler cycle.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success" data-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>';
    }

    /**
     * Generates the configurator for the selector matrix form
     */
    protected function getCartConfigurator(string $url): string
    {
        $localizerConfigurator = '<div class="localizer-matrix-configurator"><ul class="list-inline">';
        $localizerConfigurator .= $this->getLocalizerSelector($url);
        if ($this->localizerId) {
            $localizerConfigurator .= $this->getUserSelector($url);
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
            $this->localizerPid = (int)$this->availableLocalizers[$this->localizerId]['pid'];
        }
        $localizerSelector = '<li class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" id="localizerDropdownMenu1" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' .
            $GLOBALS['LANG']->sL(
                'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_cart.xlf:localizer.selector'
            ) .
            '<span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="localizerDropdownMenu1">';
        if (!empty($this->availableLocalizers)) {
            foreach ($this->availableLocalizers as $uid => $localizer) {
                $id = (int)$uid;
                $pid = (int)$localizer['pid'];
                $selected = '';
                if ($id === $this->localizerId) {
                    $selected = ' class="active"';
                }
                $localizerSelector .= '<li' . $selected . '>
                    <a class="dropdown-item" href="' . $url . '&id=' . $this->id . '&selected_localizer=' . $id . '&selected_localizerPid=' . $pid . '">' . $localizer['title'] . '</a>
                </li>';
            }
        }

        return $localizerSelector . '</ul></li>';
    }

    /**
     * Generates the user selector for the configurator
     * which might be used to select another backend user
     */
    protected function getUserSelector(string $url): string
    {
        $availableUsers = $this->cartRepository->loadAvailableUsers();
        $userSelector = '<li class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" id="localizerDropdownMenu2" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' .
            $GLOBALS['LANG']->sL(
                'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_cart.xlf:user.selector'
            ) .
            '<span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="localizerDropdownMenu2">';
        if (!empty($availableUsers)) {
            foreach ($availableUsers as $user) {
                $id = (int)$user['uid'];
                $selected = '';
                if ($id === $this->userId || !$this->userId && $id === $this->getBackendUser()->user['uid']) {
                    $selected = ' class="active"';
                }
                $userSelector .= '<li' . $selected . '>
                    <a class="dropdown-item" href="' . $url . '&id=' . $this->id . '&selected_user=' . $id . '&selected_localizer=' . $this->localizerId . '">' .
                    ($user['realName'] ?: $user['username']) .
                    '</a>
                </li>';
            }
        }
        return $userSelector . '</ul>
          </li>';
    }

    /**
     * Generates the JSON data for the jQuery handler dealing with additional localizer information
     */
    protected function generateRecordInfo(): string
    {
        $recordInfo = $this->cartRepository->getRecordInfo($this->localizerId, $this->statusClasses, $this->userId);
        return 'var localizerRecordInfo = \'' . json_encode($recordInfo) . '\';';
    }
}
