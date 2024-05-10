<?php

declare(strict_types=1);

/**
 * Definitions for modules provided by EXT:localizer
 * @see https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/HowTo/BackendModule/ModuleConfiguration.html
 */

use Localizationteam\Localizer\Controller\CartController;
use Localizationteam\Localizer\Controller\SelectorController;
use Localizationteam\Localizer\Controller\SettingsController;

$lll = 'LLL:EXT:localizer/Resources/Private/Language/';

return [
    'localizer' => [
        'labels' => $lll . 'locallang_localizer.xlf',
        'access' => 'user,group',
        'iconIdentifier' => 'extensionIcon',
        'position' => ['after' => 'l10nmgr'],
        'navigationComponent' => '@typo3/backend/page-tree/page-tree-element',
    ],
    'localizer_selector' => [
        'parent' => 'localizer',
        'access' => 'user,group', // user, admin or systemMaintainer
        'path' => '/module/localizer/selector',
        'iconIdentifier' => 'module-selector',
        'labels' => $lll . 'locallang_localizer_selector.xlf',
        'navigationComponent' => '@typo3/backend/page-tree/page-tree-element',
        'routes' => [
            '_default' => [
                'target' => SelectorController::class . '::mainAction',
            ],
        ],
    ],
    'localizer_cart' => [
        'parent' => 'localizer',
        'access' => 'user,group', // user, admin or systemMaintainer
        'path' => '/module/localizer/cart',
        'iconIdentifier' => 'module-cart',
        'labels' => $lll . 'locallang_localizer_cart.xlf',
        'navigationComponent' => '@typo3/backend/page-tree/page-tree-element',
        'routes' => [
            '_default' => [
                'target' => CartController::class . '::mainAction',
            ],
        ],
    ],
    'localizer_settings' => [
        'parent' => 'localizer',
        'access' => 'user,group', // user, admin or systemMaintainer
        'path' => '/module/localizer/settings',
        'iconIdentifier' => 'module-settings',
        'labels' => $lll . 'locallang_localizer_settings.xlf',
        'navigationComponent' => '@typo3/backend/page-tree/page-tree-element',
        'routes' => [
            '_default' => [
                'target' => SettingsController::class . '::handleRequest',
            ],
        ],
    ],
];
