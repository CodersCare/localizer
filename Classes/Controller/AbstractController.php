<?php

namespace Localizationteam\Localizer\Controller;

use Localizationteam\Localizer\Model\Repository\AbstractRepository;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\BaseScriptClass;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract for modules of the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 */
abstract class AbstractController extends BaseScriptClass
{
    /**
     * @var array
     */
    protected $pageinfo;

    /**
     * Document template object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var int
     */
    protected $localizerId;

    /**
     * @var int
     */
    protected $localizerPid;

    /**
     * @var array
     */
    protected $legend = [];

    /**
     * @var array
     */
    protected $statusClasses = [];

    /**
     * @var string
     */
    protected $backPath;

    /**
     * @var AbstractRepository
     */
    protected $abstractRepository;

    /**
     * @var array
     */
    protected $availableLocalizers;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->abstractRepository = GeneralUtility::makeInstance(AbstractRepository::class);
    }

    /**
     * Injects the request object for the current request or subrequest
     * Then checks for module functions that have hooked in, and renders menu etc.
     *
     * @param ServerRequestInterface $request the current request
     * @param Response $response
     * @return Response the response with the content
     */
    public function mainAction(ServerRequestInterface $request, Response $response)
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();
        $this->main();
        $this->moduleTemplate->setContent($this->content);
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Initializing the module
     *
     * @return array
     */
    public function init()
    {
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(1);
        $this->id = (int)GeneralUtility::_GP('id');
        $this->availableLocalizers = $this->abstractRepository->loadAvailableLocalizers();
        $this->localizerId = (int)GeneralUtility::_GP('selected_localizer');
        $this->localizerPid = (int)GeneralUtility::_GP('selected_localizerPid');

        if (empty($this->id)) {
            $this->id = hexdec(ltrim($this->getBackendUser()->uc['BackendComponents']['States']['Pagetree']->stateHash->lastSelectedNode,
                'p'));
        }

        $this->legend = [
            '10' => [
                'cssClass' => 'changed-after-translation',
                'label'    => 'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:legend.changed.after.translation',
            ],
            '20' => [
                'cssClass' => 'not-translated',
                'label'    => 'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:legend.not.translated',
            ],
            '30' => [
                'cssClass' => 'sent-to-translation',
                'label'    => 'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:legend.sent.to.translation',
            ],
            '40' => [
                'cssClass' => 'back-from-translation',
                'label'    => 'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:legend.back.from.translation',
            ],
            '50' => [
                'cssClass' => 'translated',
                'label'    => 'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf:legend.translated',
            ],
        ];

        $this->statusClasses = [
            '0'  => $this->legend['20'],
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
        ];

        if (!empty($this->localizerId) && !empty($this->availableLocalizers[$this->localizerId])) {
            $localizer = $this->availableLocalizers[$this->localizerId];
            $this->getLocalizerSettings($localizer['type']);
        }
        return $localizer;
    }

    /**
     * Override default settings based on registered type settings for a specific localizer
     *
     * @return void
     */
    protected function getLocalizerSettings($type)
    {

    }

    /**
     * Main function, starting the rendering of the list.
     *
     * @return void
     */
    abstract protected function main();

    /**
     * Initialize function menu array
     *
     * @return void
     */
    public function menuConfig()
    {
        $this->MOD_MENU = [
            'bigControlPanel' => '',
            'clipBoard'       => '',
            'localization'    => '',
        ];
    }

    /**
     * @return mixed
     */
    protected function getBackPath()
    {
        return $this->backPath;
    }

    /**
     * Outputting the accumulated content to screen
     *
     * @return void
     */
    protected function printContent()
    {
        echo $this->content;
    }
}