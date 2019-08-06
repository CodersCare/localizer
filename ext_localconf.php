<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript',
    'Localizer for TYPO3');

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
    options.saveDocNew.tx_localizer_settings=1
    ');

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['localizer'] =
        'Localizationteam\Localizer\Hooks\DataHandler';

    $_EXTCONF_ARRAY = unserialize($_EXTCONF);
    if ($_EXTCONF_ARRAY['enable_stat_hook']) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks']['tx_localizer'] = 'Localizationteam\\Localizer\\Hooks\\DataHandler->recStatInfo';
    }

    // register BE AJAX controller
    $TYPO3_CONF_VARS['BE']['AJAX']['tx_localizer::controller'] =
        $extPath . 'Classes/Ajax/Controller.php:\\Localizationteam\\Localizer\\Ajax\\Controller->init';

    // Register l10nmgr hook
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['exportView'][$_EXTKEY] = Localizationteam\Localizer\Hooks\L10nMgrExportHandler::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Localizationteam\\Localizer\\Task\\FileSenderTask'] = [
        'extension'   => $_EXTKEY,
        'title'       => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:fileSenderTask_title',
        'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:fileSenderTask_desc',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Localizationteam\\Localizer\\Task\\StatusRequesterTask'] = [
        'extension'   => $_EXTKEY,
        'title'       => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:statusRequesterTask_title',
        'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:statusRequesterTask_desc',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Localizationteam\\Localizer\\Task\\ErrorResetterTask'] = [
        'extension'   => $_EXTKEY,
        'title'       => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:errorResetterTask_title',
        'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:errorResetterTask_desc',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Localizationteam\\Localizer\\Task\\FileDownloaderTask'] = [
        'extension'   => $_EXTKEY,
        'title'       => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:fileDownloaderTask_title',
        'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:fileDownloaderTask_desc',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Localizationteam\\Localizer\\Task\\FileImporterTask'] = [
        'extension'   => $_EXTKEY,
        'title'       => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:fileImporterTask_title',
        'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:fileImporterTask_desc',
    ];
}
