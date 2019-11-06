<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', [
    'localizer_include_with_automatic_export' => [
        'exclude' => 1,
        'label'   => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:pages.localizer_include_with_automatic_export',
        'config'  => [
            'type'  => 'check',
            'items' => [
                [
                    'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:pages.localizer_include_with_automatic_export.exclude',
                    '',
                ],
            ],
        ],
    ],
    'localizer_include_with_specific_export' => [
        'exclude' => 1,
        'label'   => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:pages.localizer_include_with_specific_export',
        'config'  => [
            'type'                             => 'select',
            'foreign_table'                    => 'tx_localizer_settings',
            'foreign_table_where'              => 'AND {#tx_localizer_settings}.{#allow_adding_to_export} = 1',
            'renderType'                       => 'selectMultipleSideBySide',
            'size'                             => 2,
            'autoSizeMax'                      => 5,
            'MM'                               => 'tx_localizer_settings_pages_mm',
        ],
    ],
]);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 'l10nmgr_configuration',
    'localizer_include_with_automatic_export, --linebreak--, localizer_include_with_specific_export');
