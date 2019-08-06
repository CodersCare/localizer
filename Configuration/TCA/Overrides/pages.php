<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', [
    'localizer_include_with_automatic_export' => [
        'exclude' => 1,
        'label'   => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:pages.localizer_include_with_automatic_export',
        'config'  => [
            'type' => 'check',
            'items' => [
                [
                    'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:pages.localizer_include_with_automatic_export.exclude',
                    '',
                ],
            ],
        ],
    ],
]);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 'l10nmgr_configuration',
    'localizer_include_with_automatic_export');
