<?php

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\AddFileToMatrix;
use Localizationteam\Localizer\Data;
use Localizationteam\Localizer\Language;
use Localizationteam\Localizer\Model\Repository\AutomaticExportRepository;
use Localizationteam\Localizer\Model\Repository\SelectorRepository;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
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
     * @throws Exception
     */
    public function init($id = 0)
    {
        $this->selectorRepository = GeneralUtility::makeInstance(SelectorRepository::class);
        $this->automaticExportRepository = GeneralUtility::makeInstance(AutomaticExportRepository::class);
        $this->availableLocalizers = $this->selectorRepository->loadAvailableLocalizers();
        if (!empty($this->availableLocalizers)) {
            foreach ($this->availableLocalizers as $key => $localizer) {
                if ((int)$localizer['allow_adding_to_export'] > 0
                    || (int)$localizer['collect_pages_marked_for_export'] > 0) {
                    $this->initRun();
                } else {
                    unset($this->availableLocalizers[$key]);
                }
            }
        };
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        if ($this->canRun() === true) {
            foreach ($this->availableLocalizers as $localizer) {
                $this->exportScheduledRecords($localizer);
            }
        }
    }

    /**
     * @param $localizer
     * @throws Exception
     */
    protected function exportScheduledRecords($localizer)
    {
        // $unfinishedButSentCarts = $this->automaticExportRepository->loadUnfinishedButSentCarts((int)$localizer['uid']);
        $alreadyHandledPages = [];
        /*foreach ($unfinishedButSentCarts as $cart) {
            ArrayUtility::mergeRecursiveWithOverrule($alreadyHandledPages,
                $this->selectorRepository->loadAvailablePages(0, (int)$cart['uid']));
        }*/
        if ($localizer['collect_pages_marked_for_export']) {
            $pagesConfiguredForAutomaticExport = $this->automaticExportRepository->loadPagesConfiguredForAutomaticExport((int)$localizer['automatic_export_minimum_age'],
                array_keys($alreadyHandledPages));
        }
        if ($localizer['allow_adding_to_export']) {
            $pagesAddedToThisAutomaticExport = $this->automaticExportRepository->loadPagesAddedToSpecificAutomaticExport((int)$localizer['uid'],
                (int)$localizer['automatic_export_minimum_age'],
                array_keys($alreadyHandledPages));
        }
        $pagesForAutomaticExport = array_merge($pagesConfiguredForAutomaticExport ? : [], $pagesAddedToThisAutomaticExport ? : []);
        if (!empty($pagesForAutomaticExport)) {
            foreach ($pagesForAutomaticExport as $page) {
                $translatableTables = $this->findTranslatableTables((int)$page['uid']);
                $configuration = [
                    'tables' => array_flip(array_keys($translatableTables)),
                ];
                $recordsToBeExported = $this->selectorRepository->getRecordsOnPages((int)$page['uid'],
                    [(int)$page['uid'] => 1], $translatableTables, $configuration);
                if (!empty($recordsToBeExported) && !empty($recordsToBeExported['records'])) {
                    $translationConfigurationProvider = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
                    $systemLanguages = $translationConfigurationProvider->getSystemLanguages();
                    $localizerLanguages = $this->selectorRepository->getLocalizerLanguages((int)$localizer['uid']);
                    if (!empty($localizerLanguages['source']) && !empty($localizerLanguages['target'])) {
                        $automaticTriples = [];
                        $languageArray = array_flip(GeneralUtility::intExplode(',', $localizerLanguages['target'], true));
                        $configuration['languages'] = [];
                        foreach ($systemLanguages as $language) {
                            if (isset($languageArray[(int)$language['static_lang_isocode']])) {
                                $configuration['languages'][(int)$language['uid']] = 1;
                            }
                        }
                        foreach ($recordsToBeExported['records'] as $table => $records) {
                            if (!empty($records)) {
                                if (!array_key_exists($table, $automaticTriples)) {
                                    $automaticTriples[$table] = [];
                                }
                                foreach ($records as $uid => $record) {
                                    foreach ($configuration['languages'] as $language => $value) {
                                        $automaticTriples[$table][$uid][$language] = 1;
                                    }
                                }
                            }
                        }
                        if (!empty($recordsToBeExported['referencedRecords'])) {
                            foreach ($recordsToBeExported['referencedRecords'] as $referencedTables => $referencedRecords) {
                                if (!empty($referencedRecords)) {
                                    foreach ($referencedRecords as $parentId => $childTable) {
                                        if (!empty($childTable)) {
                                            $childTableName = key($childTable);
                                            if (!array_key_exists($childTableName, $automaticTriples)) {
                                                $automaticTriples[$childTableName] = [];
                                            }
                                            foreach ($childTable as $childArray) {
                                                $child = $childArray[key($childArray)];
                                                foreach ($configuration['languages'] as $language => $value) {
                                                    $automaticTriples[$childTableName][(int)$child['uid']][$language] = 1;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $cartId = (int)$this->selectorRepository->createNewCart((int)$localizer['pid'],
                            (int)$localizer['uid']);
                        $this->selectorRepository->storeConfiguration((int)$localizer['pid'], $cartId, $configuration);
                        $pageIds = [(int)$page['uid'] => (int)$page['uid']];
                        $this->automaticExportRepository->storeCart($pageIds, $cartId, $configuration,
                            $automaticTriples);
                        $configurationId = $this->selectorRepository->storeL10nmgrConfiguration((int)$localizer['pid'],
                            (int)$localizer['uid'],
                            $cartId, $configuration);
                        $this->selectorRepository->finalizeCart((int)$localizer['uid'], $cartId, $configurationId);
                        /** @var FileExporter $fileExporter */
                        $fileExporter = GeneralUtility::makeInstance(FileExporter::class);
                        $fileExporter->init($cartId);
                        $fileExporter->run();
                    }
                }
            }
        }
    }

    /**
     * @param $pid
     * @return array
     */
    protected function findTranslatableTables($pid)
    {
        $translatableTables = ['pages' => $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['ctrl']['title'])];
        foreach (array_keys($GLOBALS['TCA']) as $table) {
            if (BackendUtility::isTableLocalizable($table)) {
                $recordExists = $this->getDatabaseConnection()
                    ->exec_SELECTgetSingleRow('*', $table, 'pid=' . $pid .
                        BackendUtility::BEenableFields($table) .
                        BackendUtility::deleteClause($table));
                if (!empty($recordExists) &&
                    $table !== 'pages_language_overlay'
                ) {
                    $translatableTables[$table] = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
                }
            }
        }
        return $translatableTables;
    }

    public function finish($time)
    {

    }

}