<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\Model\Repository\AutomaticExportRepository;
use Localizationteam\Localizer\Model\Repository\SelectorRepository;
use Localizationteam\Localizer\Traits\AddFileToMatrix;
use Localizationteam\Localizer\Traits\ConnectionPoolTrait;
use Localizationteam\Localizer\Traits\Data;
use Localizationteam\Localizer\Traits\Language;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * FileExporter takes care to create file(s) that can be sent to Localizer
 *
 * @author      Jo Hasenau<info@cybercraft.de>
 */
class AutomaticExporter extends AbstractCartHandler
{
    use AddFileToMatrix;
    use ConnectionPoolTrait;
    use Data;
    use Language;

    protected string $uploadPath = '';

    protected SelectorRepository $selectorRepository;

    protected AutomaticExportRepository $automaticExportRepository;

    protected array $content = [];

    protected array $triples = [];

    protected array $exportTree = [];

    protected array $availableLocalizers = [];

    /**
     * @throws Exception
     */
    public function init(int $id = 0): void
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
        }
    }

    /**
     * @throws Exception
     */
    public function run(): void
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
        $pagesConfiguredForAutomaticExport = [];
        if ($localizer['collect_pages_marked_for_export']) {
            $pagesConfiguredForAutomaticExport = $this->automaticExportRepository->loadPagesConfiguredForAutomaticExport(
                (int)$localizer['automatic_export_minimum_age'],
                array_keys($alreadyHandledPages)
            );
        }
        $pagesAddedToThisAutomaticExport = [];
        if ($localizer['allow_adding_to_export']) {
            $pagesAddedToThisAutomaticExport = $this->automaticExportRepository->loadPagesAddedToSpecificAutomaticExport(
                (int)$localizer['uid'],
                (int)$localizer['automatic_export_minimum_age'],
                array_keys($alreadyHandledPages)
            );
        }
        $pagesForAutomaticExport = array_merge(
            $pagesConfiguredForAutomaticExport,
            $pagesAddedToThisAutomaticExport
        );
        if (!empty($pagesForAutomaticExport)) {
            foreach ($pagesForAutomaticExport as $page) {
                $translatableTables = $this->findTranslatableTables((int)$page['uid']);
                $configuration = [
                    'tables' => array_flip(array_keys($translatableTables)),
                    'sortexports' => 1,
                ];
                $recordsToBeExported = $this->selectorRepository->getRecordsOnPages(
                    (int)$page['uid'],
                    [(int)$page['uid'] => 1],
                    $translatableTables,
                    $configuration
                );
                if (!empty($recordsToBeExported) && !empty($recordsToBeExported['records'])) {
                    $translationConfigurationProvider = GeneralUtility::makeInstance(
                        TranslationConfigurationProvider::class
                    );
                    $systemLanguages = $translationConfigurationProvider->getSystemLanguages();
                    $localizerLanguages = $this->selectorRepository->getLocalizerLanguages((int)$localizer['uid']);
                    if (!empty($localizerLanguages['source']) && !empty($localizerLanguages['target'])) {
                        $automaticTriples = [];
                        $languageArray = array_flip(
                            GeneralUtility::intExplode(
                                ',',
                                $localizerLanguages['target'],
                                true
                            )
                        );
                        $configuration['languages'] = [];
                        foreach ($systemLanguages as $language) {
                            // @todo The key 'static_lang_isocode' is not present anymore since TYPO3 v10.
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
                        $cartId = $this->selectorRepository->createNewCart(
                            (int)$localizer['pid'],
                            (int)$localizer['uid']
                        );
                        $this->selectorRepository->storeConfiguration((int)$localizer['pid'], $cartId, $configuration);
                        $pageIds = [(int)$page['uid'] => (int)$page['uid']];
                        $this->automaticExportRepository->storeCart(
                            $pageIds,
                            $cartId,
                            $configuration,
                            $automaticTriples
                        );
                        $configurationId = $this->selectorRepository->storeL10nmgrConfiguration(
                            (int)$localizer['pid'],
                            (int)$localizer['uid'],
                            $cartId,
                            $configuration
                        );
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

    protected function findTranslatableTables(int $pid): array
    {
        $translatableTables = ['pages' => $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['ctrl']['title'])];

        // TODO: This snipped does mainly the same like the one in SelectorController:611.
        //       But it checks first if the table is translatable, which might lead to less SQL Queries
        foreach (array_keys($GLOBALS['TCA']) as $table) {
            if (BackendUtility::isTableLocalizable($table)) {
                // TODO: We can use the selectorRepository->findRecordByPid here.
                //       But first we need to figure out if the Restrictions there are really needed.
                // $selectorRepository = GeneralUtility::makeInstance(SelectorRepository::class);
                // $recordExists = $selectorRepository->findRecordByPid($pid, $table);

                $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable($table);
                $result = $queryBuilder
                    ->select('*')
                    ->from($table)
                    ->where(
                        $queryBuilder->expr()->eq(
                            'pid',
                            $pid
                        )
                    )
                    ->executeQuery();
                $recordExists = $this->fetchOne($result);
                if (!empty($recordExists)) {
                    $translatableTables[$table] = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
                }
            }
        }
        return $translatableTables;
    }

    /**
     * @param $time
     */
    public function finish($time): void
    {
    }

    protected function acquire(): bool
    {
        return false;
    }
}
