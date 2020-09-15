<?php

namespace Localizationteam\Localizer\Controller;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * Module 'Settings' for the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 */
class SettingsController extends AbstractController
{
    /**
     * @var int
     */
    protected $pointer;

    /**
     * @var string
     */
    protected $imagemode;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $search_field;

    /**
     * @var int
     */
    protected $search_levels;

    /**
     * @var int
     */
    protected $showLimit;

    /**
     * @var string
     */
    protected $returnUrl;

    /**
     * @var array
     */
    protected $cmd;

    /**
     * @var string
     */
    protected $cmd_table;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'localizer_localizersettings';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->MCONF = [
            'name' => $this->moduleName,
        ];
        $this->getBackendUser()->modAccess($this->MCONF, 1);
        $this->getLanguageService()->includeLLFile('EXT:localizer/Resources/Private/Language/locallang_localizer_settings.xlf');
    }

    /**
     * Initializing the module
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->MCONF = $GLOBALS['MCONF'];
        $this->id = (int)GeneralUtility::_GP('id');
        $this->pointer = GeneralUtility::_GP('pointer');
        $this->imagemode = GeneralUtility::_GP('imagemode');
        $_GET['table'] = 'tx_localizer_settings';
        $this->table = GeneralUtility::_GP('table');
        $this->search_field = GeneralUtility::_GP('search_field');
        $this->search_levels = (int)GeneralUtility::_GP('search_levels');
        $this->showLimit = GeneralUtility::_GP('showLimit');
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        $this->cmd = GeneralUtility::_GP('cmd');
        $this->cmd_table = GeneralUtility::_GP('cmd_table');
    }

    /**
     * Main function, starting the rendering of the list.
     *
     * @return void
     */
    protected function main()
    {
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $access = is_array($this->pageinfo) ? 1 : 0;
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
        /** @var $dblist DatabaseRecordList */
        $dblist = GeneralUtility::makeInstance('TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList');
        $dblist->backPath = $GLOBALS['BACK_PATH'];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $dblist->script = $uriBuilder->buildUriFromRoute('web_list');
        $dblist->calcPerms = $this->getBackendUser()->calcPerms($this->pageinfo);
        $dblist->thumbs = $this->getBackendUser()->uc['thumbnailsByDefault'];
        $dblist->returnUrl = $this->returnUrl;
        $dblist->allFields = $this->MOD_SETTINGS['bigControlPanel'] || $this->table ? 1 : 0;
        $dblist->localizationView = $this->MOD_SETTINGS['localization'];
        $dblist->showClipboard = 1;
        $dblist->disableSingleTableView = $this->modTSconfig['properties']['disableSingleTableView'];
        $dblist->listOnlyInSingleTableMode = $this->modTSconfig['properties']['listOnlyInSingleTableView'];
        $dblist->hideTables = $this->modTSconfig['properties']['hideTables'];
        $dblist->hideTranslations = $this->modTSconfig['properties']['hideTranslations'];
        $dblist->tableTSconfigOverTCA = $this->modTSconfig['properties']['table.'];
        $dblist->alternateBgColors = $this->modTSconfig['properties']['alternateBgColors'] ? 1 : 0;
        $dblist->allowedNewTables = GeneralUtility::trimExplode(',',
            $this->modTSconfig['properties']['allowedNewTables'], true);
        $dblist->deniedNewTables = GeneralUtility::trimExplode(',', $this->modTSconfig['properties']['deniedNewTables'],
            true);
        $dblist->newWizards = $this->modTSconfig['properties']['newWizards'] ? 1 : 0;
        $dblist->pageRow = $this->pageinfo;
        $dblist->counter++;
        $dblist->MOD_MENU = ['bigControlPanel' => '', 'clipBoard' => '', 'localization' => ''];
        $dblist->modTSconfig = $this->modTSconfig;
        $clickTitleMode = trim($this->modTSconfig['properties']['clickTitleMode']);
        $dblist->clickTitleMode = $clickTitleMode === '' ? 'edit' : $clickTitleMode;
        $dblist->clipObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Clipboard\\Clipboard');
        $dblist->clipObj->initializeClipboard();
        $CB = GeneralUtility::_GET('CB');
        if ($this->cmd == 'setCB') {
            $CB['el'] = $dblist->clipObj->cleanUpCBC(array_merge(GeneralUtility::_POST('CBH'),
                (array)GeneralUtility::_POST('CBC')), $this->cmd_table);
        }
        if (!$this->MOD_SETTINGS['clipBoard']) {
            $CB['setP'] = 'normal';
        }
        $dblist->clipObj->setCmd($CB);
        $dblist->clipObj->cleanCurrent();
        $dblist->clipObj->endClipboard();
        $dblist->dontShowClipControlPanels = (!$this->MOD_SETTINGS['bigControlPanel'] && $dblist->clipObj->current == 'normal' && !$this->modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers']);
        if ($access || ($this->id === 0 && $this->search_levels > 0 && strlen($this->search_field) > 0)) {
            if ($this->cmd == 'delete') {
                $items = $dblist->clipObj->cleanUpCBC(GeneralUtility::_POST('CBC'), $this->cmd_table, 1);
                if (count($items)) {
                    $cmd = [];
                    foreach ($items as $iK => $value) {
                        $iKParts = explode('|', $iK);
                        $cmd[$iKParts[0]][$iKParts[1]]['delete'] = 1;
                    }
                    $tce = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
                    $tce->stripslashes_values = 0;
                    $tce->start([], $cmd);
                    $tce->process_cmdmap();
                    if (isset($cmd['pages'])) {
                        BackendUtility::setUpdateSignal('updatePageTree');
                    }
                    $tce->printLogErrorMessages(GeneralUtility::getIndpEnv('REQUEST_URI'));
                }
            }
            $this->pointer = MathUtility::forceIntegerInRange($this->pointer, 0, 100000);
            $dblist->start($this->id, $this->table, $this->pointer, $this->search_field, 99, $this->showLimit);
            $dblist->setDispFields();
            if (ExtensionManagementUtility::isLoaded('version')) {
                $dblist->HTMLcode .= $this->moduleTemplate->getVersionSelector($this->id);
            }
            $dblist->generateList();
            $listUrl = substr($dblist->listURL(), strlen($GLOBALS['BACK_PATH']));
            $this->moduleTemplate->addJavaScriptCode('localizer_settings_list', '
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
					window.location.href="' . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '&edit["+table+"]["+idList+"]=edit"+addParams;
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

				if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';
			');
        }
        $header = 'LOCALIZER Settings';
        if (isset($this->pageinfo['title'])) {
            $header .= ' : ';
        }
        $this->content = $this->moduleTemplate->header($header . $this->pageinfo['title']);
        if ($this->id > 0) {
            $this->content .= '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">';
            $this->content .= $dblist->HTMLcode;
            $this->content .= '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';
            if ($dblist->HTMLcode) {
                if ($dblist->table) {
                    $this->content .= $dblist->fieldSelectBox($dblist->table);
                }
                $this->content .= '
					<div id="typo3-listOptions">
						<form action="" method="post">';
                if ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'selectable') {
                    $this->content .= BackendUtility::getFuncCheck($this->id, 'SET[bigControlPanel]',
                        $this->MOD_SETTINGS['bigControlPanel'], '', $this->table ? '&table=' . $this->table : '',
                        'id="checkLargeControl"');
                    $this->content .= '<label for="checkLargeControl">' . BackendUtility::wrapInHelp('xMOD_csh_corebe',
                            'list_options', $GLOBALS['LANG']->getLL('largeControl', true)) . '</label><br />';
                }
                if ($this->modTSconfig['properties']['enableClipBoard'] === 'selectable') {
                    if ($dblist->showClipboard) {
                        $this->content .= BackendUtility::getFuncCheck($this->id, 'SET[clipBoard]',
                            $this->MOD_SETTINGS['clipBoard'], '', $this->table ? '&table=' . $this->table : '',
                            'id="checkShowClipBoard"');
                        $this->content .= '<label for="checkShowClipBoard">' . BackendUtility::wrapInHelp('xMOD_csh_corebe',
                                'list_options',
                                $GLOBALS['LANG']->getLL('showClipBoard', true)) . '</label><br />';
                    }
                }
                if ($this->modTSconfig['properties']['enableLocalizationView'] === 'selectable') {
                    $this->content .= BackendUtility::getFuncCheck($this->id, 'SET[localization]',
                        $this->MOD_SETTINGS['localization'], '', $this->table ? '&table=' . $this->table : '',
                        'id="checkLocalization"');
                    $this->content .= '<label for="checkLocalization">' . BackendUtility::wrapInHelp('xMOD_csh_corebe',
                            'list_options', $GLOBALS['LANG']->getLL('localization', true)) . '</label><br />';
                }
                $this->content .= '
						</form>
					</div>';
            } else {
                $this->content .= '<div class="error">No Localizer settings found in this rootline</div>';
            }
        } else {
            $this->content .= '<div class="error">Please select a page</div>';
        }
    }
}