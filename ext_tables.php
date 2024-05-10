<?php

// TODO: Remove the file when upgrading to v13 as we removing v11 support
use Localizationteam\Localizer\Controller\CartController;
use Localizationteam\Localizer\Controller\LocalizerController;
use Localizationteam\Localizer\Controller\SelectorController;
use Localizationteam\Localizer\Controller\SettingsController;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3')) {
    die('Access denied.');
}

/**
 * Registers a Backend Module
 */
ExtensionManagementUtility::addModule(
    'localizer',
    '', // Submodule key
    '',    // Position
    '',
    [
        'routeTarget' => LocalizerController::class . '::mainAction',
        'access' => 'user,group',
        'name' => 'localizer',
        'icon' => 'EXT:localizer/Resources/Public/Icons/module-localizer.svg',
        'labels' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer.xlf',
    ]
);

/**
 * Registers a Backend Module
 */
ExtensionManagementUtility::addModule(
    'localizer', // Make module a submodule of 'Localizer'
    'localizerselector', // Submodule key
    '',    // Position
    '',
    [
        'routeTarget' => SelectorController::class . '::mainAction',
        'access' => 'user,group',
        'name' => 'localizer_localizerselector',
        'icon' => 'EXT:localizer/Resources/Public/Icons/module-localizer-selector.svg',
        'labels' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_selector.xlf',
        'navigationComponentId' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement',
    ]
);

/**
 * Registers a Backend Module
 */
ExtensionManagementUtility::addModule(
    'localizer', // Make module a submodule of 'Localizer'
    'localizercart', // Submodule key
    '',    // Position
    '',
    [
        'routeTarget' => CartController::class . '::mainAction',
        'access' => 'user,group',
        'name' => 'localizer_localizercart',
        'icon' => 'EXT:localizer/Resources/Public/Icons/module-localizer-cart.svg',
        'labels' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_cart.xlf',
        'navigationComponentId' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement',
    ]
);

/**
 * Registers a Backend Module
 */
ExtensionManagementUtility::addModule(
    'localizer', // Make module a submodule of 'Localizer'
    'localizersettings', // Submodule key
    '',    // Position
    '',
    [
        'routeTarget' => SettingsController::class . '::mainAction',
        'access' => 'user,group',
        'name' => 'localizer_localizersettings',
        'icon' => 'EXT:localizer/Resources/Public/Icons/module-localizer-settings.svg',
        'labels' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_localizer_settings.xlf',
        'navigationComponentId' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement',
    ]
);


ExtensionManagementUtility::allowTableOnStandardPages('tx_localizer_settings');
ExtensionManagementUtility::allowTableOnStandardPages('tx_localizer_cart');
ExtensionManagementUtility::allowTableOnStandardPages('tx_localizer_settings_l10n_exportdata_mm');
