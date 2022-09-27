<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Controller;

use Localizationteam\Localizer\Model\Repository\AbstractRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract for modules of the 'localizer' extension.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
abstract class AbstractController extends BaseModule
{
    /**
     * @var array
     */
    protected array $pageinfo;

    /**
     * Document template object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var int
     */
    protected int $localizerId;

    /**
     * @var int
     */
    protected int $localizerPid;

    /**
     * @var array
     */
    protected array $legend = [];

    /**
     * @var array
     */
    protected array $statusClasses = [];

    /**
     * @var string
     */
    protected string $backPath;

    /**
     * @var AbstractRepository
     */
    protected AbstractRepository $abstractRepository;

    /**
     * @var array
     */
    protected array $availableLocalizers;

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
     * @return ResponseInterface the response with the content
     */
    public function mainAction(): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = func_num_args() === 2 ? func_get_arg(1) : null;
        $this->init();
        $this->main();
        $this->moduleTemplate->setContent($this->content);
        if ($response !== null) {
            $response->getBody()->write($this->moduleTemplate->renderContent());
        } else {
            $response = new HtmlResponse($this->moduleTemplate->renderContent());
        }
        return $response;
    }

    /**
     * Initializing the module
     *
     * @return array
     */
    public function init(): array
    {
        $localizer = [];
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(1);
        $this->id = (int)GeneralUtility::_GP('id');
        $this->availableLocalizers = $this->abstractRepository->loadAvailableLocalizers();
        $this->localizerId = (int)GeneralUtility::_GP('selected_localizer');
        $this->localizerPid = (int)GeneralUtility::_GP('selected_localizerPid');

        if (empty($this->id)) {
            $this->id = hexdec(
                ltrim(
                    $this->getBackendUser()->uc['BackendComponents']['States']['Pagetree']->stateHash->lastSelectedNode,
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
    abstract protected function main();

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

    /**
     * @return string
     */
    protected function getBackPath(): string
    {
        return $this->backPath;
    }

    /**
     * Outputting the accumulated content to screen
     */
    protected function printContent()
    {
        echo $this->content;
    }
}
