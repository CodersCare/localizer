<?php

namespace Localizationteam\Localizer\Handler;

use Localizationteam\Localizer\AddFileToMatrix;
use Localizationteam\Localizer\Data;
use Localizationteam\Localizer\Language;
use Localizationteam\Localizer\Model\Repository\AutomaticExportRepository;
use Localizationteam\Localizer\Model\Repository\CartRepository;
use Localizationteam\Localizer\Model\Repository\SelectorRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * FileExporter takes care to create file(s) that can be sent to Localizer
 *
 * @author      Jo Hasenau<info@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 *
 */
class AutomaticExporter extends AbstractCartHandler
{
    use Data, Language, AddFileToMatrix;

    /**
     * @var string
     */
    protected $uploadPath = '';

    /**
     * @var SelectorRepository
     */
    protected $selectorRepository;

    /**
     * @var CartRepository
     */
    protected $cartRepository;

    /**
     * @var AutomaticExportRepository
     */
    protected $automaticExportRepository;

    /**
     * @var array
     */
    protected $content = [];

    /**
     * @var array
     */
    protected $triples = [];

    /**
     * @var array
     */
    protected $exportTree = [];

    /**
     * @var array
     */
    protected $availableLocalizers = [];

    /**
     * @param $id
     * @throws \Exception
     */
    public function init($id = 0)
    {
        $this->selectorRepository = GeneralUtility::makeInstance(SelectorRepository::class);
        $this->cartRepository = GeneralUtility::makeInstance(CartRepository::class);
        $this->automaticExportRepository = GeneralUtility::makeInstance(AutomaticExportRepository::class);
        $this->availableLocalizers = $this->selectorRepository->loadAvailableLocalizers();
        if (!empty($this->availableLocalizers)) {
            foreach ($this->availableLocalizers as $key => $localizer) {
                if ((int)$localizer['automatic_export_minimum_age'] > 0) {
                    $this->initRun();
                } else {
                    unset($this->availableLocalizers[$key]);
                }
            }
        };
    }

    /**
     *
     */
    public function run()
    {
        if ($this->canRun() === true) {
            foreach ($this->availableLocalizers as $localizer) {
                $this->checkForScheduledRecords($localizer);
            }
        }
    }

    /**
     * @param $localizer
     */
    protected function checkForScheduledRecords($localizer) {
        $unfinishedButSentCarts = $this->automaticExportRepository->loadUnfinishedButSentCarts((int)$localizer['uid']);
        $alreadyHandledPages = [];
        foreach ($unfinishedButSentCarts as $cart) {
            ArrayUtility::mergeRecursiveWithOverrule($alreadyHandledPages, $this->selectorRepository->loadAvailablePages(0, (int)$cart['uid']));
        }
        $pagesConfiguredForAutomaticExport = $this->automaticExportRepository->loadPagesConfiguredForAutomaticExport((int)$localizer['automatic_export_minimum_age'], array_keys($alreadyHandledPages));
        if (!empty($pagesConfiguredForAutomaticExport)) {
            foreach ($pagesConfiguredForAutomaticExport as $page) {
                $translatableTables = $this->findTranslatableTables((int)$page['uid']);
                $configuration = [
                    'tables' => array_flip(array_keys($translatableTables))
                ];
                $recordsToBeExported = $this->selectorRepository->getRecordsOnPages((int)$page['uid'], [(int)$page['uid'] => 1], $translatableTables, $configuration);
            }
        }
    }

    /**
     * @param $pid
     * @return array
     */
    protected function findTranslatableTables($pid) {
        $translatableTables = ['pages' => $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['ctrl']['title'])];
        foreach (array_keys($GLOBALS['TCA']) as $table) {
            $recordExists = $this->getDatabaseConnection()
                ->exec_SELECTgetSingleRow('*', $table, 'pid=' . $pid .
                    BackendUtility::BEenableFields($table) .
                    BackendUtility::deleteClause($table));
            if (!empty($recordExists) &&
                BackendUtility::isTableLocalizable($table) &&
                $table !== 'pages_language_overlay'
            ) {
                $translatableTables[$table] = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
            }
        }
        return $translatableTables;
    }

    public function finish($time) {

    }

}