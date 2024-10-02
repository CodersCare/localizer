<?php

use Localizationteam\Localizer\Upgrades\LanguagesUpgradeWizard;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

if (!defined('TYPO3')) {
    die('Access denied.');
}

$extPath = ExtensionManagementUtility::extPath('localizer');

ExtensionManagementUtility::addUserTSConfig(
    '
options.saveDocNew.tx_localizer_settings=1
'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['localizer'] =
    'Localizationteam\Localizer\Hooks\DataHandlerHook';

// Enable stats
$enableStatHook = GeneralUtility::makeInstance(
    ExtensionConfiguration::class
)->get('localizer', 'enable_stat_hook');

if ($enableStatHook) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks']['tx_localizer'] = 'Localizationteam\\Localizer\\Hooks\\DataHandlerHook->recStatInfo';
}

// register BE AJAX controller
$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_localizer::controller'] =
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

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Localizationteam\\Localizer\\Task\\SuccessReporterTask'] = [
    'extension' => 'localizer',
    'title' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:successReporterTask_title',
    'description' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:successReporterTask_desc',
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['localizer_languagesUpgradeWizard'] = LanguagesUpgradeWizard::class;
