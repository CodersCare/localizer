<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Controller;

use Localizationteam\Localizer\BackendUser;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Model\Repository\CartRepository;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * Module 'Cart' for the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class CartController extends AbstractController
{
    use BackendUser;

    /**
     * @var int
     */
    protected int $pointer;

    /**
     * @var string
     */
    protected string $imagemode;

    /**
     * @var string
     */
    protected string $table;

    /**
     * @var string
     */
    protected string $search_field;

    /**
     * @var int
     */
    protected int $search_levels;

    /**
     * @var int
     */
    protected int $showLimit;

    /**
     * @var string
     */
    protected string $returnUrl;

    /**
     * @var array
     */
    protected array $cmd;

    /**
     * @var string
     */
    protected string $cmd_table;

    /**
     * @var CartRepository
     */
    protected CartRepository $cartRepository;

    /**
     * The name of the module
     *
     * @var string
     */
    protected string $moduleName = 'localizer_localizercart';

    /**
     * @var int
     */
    protected int $userId;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->MCONF = [
            'name' => $this->moduleName,
        ];
        $this->cartRepository = GeneralUtility::makeInstance(CartRepository::class);
        $this->getBackendUser()->modAccess($this->MCONF);
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
    public function init(): array
    {
        parent::init();
        $this->userId = (int)GeneralUtility::_GP('selected_user');
        $this->pointer = (int)GeneralUtility::_GP('pointer');
        $this->imagemode = (string)GeneralUtility::_GP('imagemode');
        $_GET['table'] = Constants::TABLE_LOCALIZER_CART;
        $this->table = (string)GeneralUtility::_GP('table');
        $this->search_field = (string)GeneralUtility::_GP('search_field');
        $this->search_levels = (int)GeneralUtility::_GP('search_levels');
        $this->showLimit = (int)GeneralUtility::_GP('showLimit');
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl((string)GeneralUtility::_GP('returnUrl'));
        $this->cmd = (array)GeneralUtility::_GP('cmd');
        $this->cmd_table = (string)GeneralUtility::_GP('cmd_table');
        return [];
    }

    /**
     * Main function, starting the rendering of the list.
     */
    protected function main()
    {
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Localizer/LocalizerCart');
        $this->moduleTemplate->getPageRenderer()->addCssFile(
            ExtensionManagementUtility::extPath('localizer') . 'Resources/Public/Css/localizer.css'
        );
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $access = is_array($this->pageinfo) ? 1 : 0;
        $this->MOD_SETTINGS['bigControlPanel'] = true;
        $this->MOD_SETTINGS['clipBoard'] = false;
        $this->MOD_SETTINGS['localization'] = false;
        /** @var DatabaseRecordList $dblist */
        $dblist = GeneralUtility::makeInstance('TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList');
        $dblist->backPath = $GLOBALS['BACK_PATH'];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        try {
            $dblist->script = $uriBuilder->buildUriFromRoute('web_list');
        } catch (RouteNotFoundException $e) {
        }
        $permissionBits = $this->getBackendUser()->calcPerms($this->pageinfo);
        if ((new Typo3Version())->getMajorVersion() > 10) {
            $dblist->calcPerms = new Permission($permissionBits);
        } else {
            $dblist->calcPerms = $permissionBits;
        }
        $dblist->thumbs = $this->getBackendUser()->uc['thumbnailsByDefault'];
        $dblist->returnUrl = $this->returnUrl;
        $dblist->allFields = $this->MOD_SETTINGS['bigControlPanel'] || $this->table ? 1 : 0;
        $dblist->showClipboard = 0;
        $dblist->showIcon = 0;
        $dblist->disableSingleTableView = $this->modTSconfig['properties']['disableSingleTableView'];
        $dblist->listOnlyInSingleTableMode = $this->modTSconfig['properties']['listOnlyInSingleTableView'];
        $dblist->hideTables = $this->modTSconfig['properties']['hideTables'];
        $dblist->hideTranslations = $this->modTSconfig['properties']['hideTranslations'];
        $dblist->tableTSconfigOverTCA = $this->modTSconfig['properties']['table.'];
        $dblist->alternateBgColors = $this->modTSconfig['properties']['alternateBgColors'] ? 1 : 0;
        $dblist->allowedNewTables = [];
        $dblist->deniedNewTables = [Constants::TABLE_LOCALIZER_CART];
        $dblist->setIsEditable(true);
        $dblist->pageRow = $this->pageinfo;
        $dblist->counter++;
        $dblist->MOD_MENU = ['bigControlPanel' => '', 'clipBoard' => '', 'localization' => ''];
        $dblist->modTSconfig = $this->modTSconfig;
        $dblist->clickTitleMode = '';
        $dblist->dontShowClipControlPanels = true;

        $header = 'LOCALIZER Cart';
        $this->content = $this->moduleTemplate->header($header);
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

        $this->content .= '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">';
        $this->content .= $this->getCartConfigurator($dblist->listURL());
        if ($access || ($this->id === 0 && $this->search_levels > 0 && strlen($this->search_field) > 0)) {
            $this->pointer = MathUtility::forceIntegerInRange($this->pointer, 0, 100000);
            $dblist->start($this->id, $this->table, $this->pointer, $this->search_field, 999, $this->showLimit);
            $dblist->setDispFields();
            $dblist->generateList();
            $listUrl = substr($dblist->listURL(), strlen((string)$GLOBALS['BACK_PATH']));
            $this->moduleTemplate->addJavaScriptCode(
                'localizer_cart_list',
                '
				function jumpExt(URL,anchor) {	//
					var anc = anchor?anchor:"";
					window.location.href = URL+(T3_THIS_LOCATION?"&returnUrl="+T3_THIS_LOCATION:"")+anc;
					return false;
				}
				function jumpSelf(URL) {	//
					window.location.href = URL+(T3_RETURN_URL?"&returnUrl="+T3_RETURN_URL:"");
					return false;
				}

				function setHighlight(id) {	//
					top.fsMod.recentIds["web"]=id;
					top.fsMod.navFrameHighlightedID["web"]="pages"+id+"_"+top.fsMod.currentBank;	// For highlighting

					if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav) {
						top.content.nav_frame.refresh_nav();
					}
				}
				' . $this->moduleTemplate->redirectUrls($listUrl) . '
                    // checkOffCB()
                function checkOffCB(listOfCBnames, link) {	//
                    var checkBoxes, flag, i;
                    var checkBoxes = listOfCBnames.split(",");
                    if (link.rel === "") {
                        link.rel = "allChecked";
                        flag = true;
                    } else {
                        link.rel = "";
                        flag = false;
                    }
                    for (i = 0; i < checkBoxes.length; i++) {
                        setcbValue(checkBoxes[i], flag);
                    }
                }
                    // cbValue()
                function cbValue(CBname) {	//
                    var CBfullName = "CBC["+CBname+"]";
                    return (document.dblistForm[CBfullName] && document.dblistForm[CBfullName].checked ? 1 : 0);
                }
                    // setcbValue()
                function setcbValue(CBname,flag) {	//
                    CBfullName = "CBC["+CBname+"]";
                    if(document.dblistForm[CBfullName]) {
                        document.dblistForm[CBfullName].checked = flag ? "on" : 0;
                    }
                }
				function editRecords(table,idList,addParams,CBflag) {	//
					window.location.href="' . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' . rawurlencode(
                    GeneralUtility::getIndpEnv('REQUEST_URI')
                ) . '&edit["+table+"]["+idList+"]=edit"+addParams;
				}
				function editList(table,idList) {	//
					var list="";

						// Checking how many is checked, how many is not
					var pointer=0;
					var pos = idList.indexOf(",");
					while (pos!=-1) {
						if (cbValue(table+"|"+idList.substr(pointer,pos-pointer))) {
							list+=idList.substr(pointer,pos-pointer)+",";
						}
						pointer=pos+1;
						pos = idList.indexOf(",",pointer);
					}
					if (cbValue(table+"|"+idList.substr(pointer))) {
						list+=idList.substr(pointer)+",";
					}

					return list ? list : idList;
				}

				if (top.fsMod) top.fsMod.recentIds["web"] = ' . $this->id . ';
			'
            );
            $this->moduleTemplate->addJavaScriptCode(
                'lcoalizer_cart_record_info',
                $this->generateRecordInfo()
            );
        }
        if ($this->localizerId) {
            $this->content .= $dblist->HTMLcode;
        } else {
            $dblist->HTMLcode = '';
            $this->content .= '<div class="alert alert-warning">' .
                $GLOBALS['LANG']->sL(
                    'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_cart.xlf:localizer.select'
                ) .
                '</div>';
        }
        $this->content .= '<input type="hidden" name="selected_localizer" value="' . $this->localizerId . '" />
            <input type="hidden" name="selected_localizerPid" value="' . $this->localizerPid . '" />';
        $this->content .= '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';
        if ($dblist->HTMLcode) {
            if ($dblist->table) {
                $this->content .= $dblist->fieldSelectBox($dblist->table);
            }
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
     *
     * @param string $url
     * @return string
     */
    protected function getCartConfigurator(string $url): string
    {
        $localizerConfigurator = '<div class="localizer-matrix-configurator"><ul class="list-inline">';
        $localizerConfigurator .= $this->getLocalizerSelector($url);
        if ($this->localizerId) {
            $localizerConfigurator .= $this->getUserSelector($url);
        }
        $localizerConfigurator .= '</ul></div>';
        return $localizerConfigurator;
    }

    /**
     * Generates the localizer selector for the configurator
     * which might be used to select one of the available localizer settings
     *
     * @param string $url
     * @return string
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
        $localizerSelector .= '</ul>
            </li>';
        return $localizerSelector;
    }

    /**
     * Generates the user selector for the configurator
     * which might be used to select another backend user
     *
     * @param string $url
     * @return string
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
        $userSelector .= '</ul>
            </li>';
        return $userSelector;
    }

    /**
     * Generates the JSON data for the jQuery handler dealing with additional localizer information
     *
     * @return string
     */
    protected function generateRecordInfo(): string
    {
        $recordInfo = $this->cartRepository->getRecordInfo($this->localizerId, $this->statusClasses, $this->userId);
        return 'var localizerRecordInfo = \'' . json_encode($recordInfo) . '\';';
    }
}
