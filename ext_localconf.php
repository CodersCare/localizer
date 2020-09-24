<?php

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('localizer');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'localizer',
    'Configuration/TypoScript',
    'Localizer for TYPO3'
);

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
        '
    options.saveDocNew.tx_localizer_settings=1
    '
    );

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['localizer'] =
        'Localizationteam\Localizer\Hooks\DataHandler';

    // Enable stats
    $enableStatHook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
    )->get('localizer', 'enable_stat_hook');
    if ($enableStatHook) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks']['tx_localizer'] = 'Localizationteam\\Localizer\\Hooks\\DataHandler->recStatInfo';
    }

    // register BE AJAX controller
    $TYPO3_CONF_VARS['BE']['AJAX']['tx_localizer::controller'] =
        $extPath . 'Classes/Ajax/Controller.php:\\Localizationteam\\Localizer\\Ajax\\Controller->init';

    // Register l10nmgr hook
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['exportView']['localizer'] = Localizationteam\Localizer\Hooks\L10nMgrExportHandler::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Localizationteam\\Localizer\\Task\\AutomaticExporterTask'] = [
        'extension' => 'localizer',
        'title' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:automaticExporterTask_title',
        'description' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:automaticExporterTask_desc',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Localizationteam\\Localizer\\Task\\FileSenderTask'] = [
        'extension' => 'localizer',
        'title' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:fileSenderTask_title',
        'description' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:fileSenderTask_desc',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Localizationteam\\Localizer\\Task\\StatusRequesterTask'] = [
        'extension' => 'localizer',
        'title' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:statusRequesterTask_title',
        'description' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:statusRequesterTask_desc',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Localizationteam\\Localizer\\Task\\ErrorResetterTask'] = [
        'extension' => 'localizer',
        'title' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:errorResetterTask_title',
        'description' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:errorResetterTask_desc',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Localizationteam\\Localizer\\Task\\FileDownloaderTask'] = [
        'extension' => 'localizer',
        'title' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:fileDownloaderTask_title',
        'description' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:fileDownloaderTask_desc',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Localizationteam\\Localizer\\Task\\FileImporterTask'] = [
        'extension' => 'localizer',
        'title' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:fileImporterTask_title',
        'description' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:fileImporterTask_desc',
    ];
}
