<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

$l10n = 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_localizer_cart');

return [
    'ctrl' => [
        'title' => $l10n . 'tx_localizer_cart',
        'label' => 'uid_local',
        'label_alt' => 'uid_foreign',
        'label_alt_force' => '1',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'crdate DESC',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:localizer/Resources/Public/Icons/module-localizer-cart.svg',
    ],
    'feInterface' => '',
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'uid_local' => [
            'exclude' => 1,
            'label' => $l10n . 'tx_localizer_settings_l10n_exportdata_mm.uid_local',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_localizer_settings',
                'size' => 1,
                'maxitems' => 1,
                'readOnly' => 1,
            ],
        ],
        'uid_export' => [
            'exclude' => 1,
            'label' => $l10n . 'tx_localizer_settings_l10n_exportdata_mm.uid_export',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_l10nmgr_exportdata',
                'size' => 1,
                'maxitems' => 1,
                'readOnly' => 1,
            ],
        ],
        'uid_foreign' => [
            'exclude' => 1,
            'label' => $l10n . 'tx_localizer_settings_l10n_exportdata_mm.uid_foreign',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_l10nmgr_cfg',
                'size' => 1,
                'maxitems' => 1,
                'readOnly' => 1,
            ],
        ],
        'description' => [
            'exclude' => 1,
            'label' => $l10n . 'tx_localizer_settings_l10n_exportdata_mm.description',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '255',
                'eval' => 'trim',
            ],
        ],
        'localizer_path' => [
            'exclude' => 1,
            'label' => $l10n . 'tx_localizer_settings_l10n_exportdata_mm.localizer_path',
            'config' => [
                'type' => 'text',
                'cols' => 80,
                'rows' => 2,
                'readOnly' => 1,
            ],
        ],
        'status' => [
            'exclude' => 1,
            'label' => $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [$l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.0', '0'],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . \Localizationteam\Localizer\Constants::STATUS_CART_ADDED,
                        \Localizationteam\Localizer\Constants::STATUS_CART_ADDED,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . \Localizationteam\Localizer\Constants::STATUS_CART_FINALIZED,
                        \Localizationteam\Localizer\Constants::STATUS_CART_FINALIZED,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . \Localizationteam\Localizer\Constants::STATUS_CART_FILE_EXPORTED,
                        \Localizationteam\Localizer\Constants::STATUS_CART_FILE_EXPORTED,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . \Localizationteam\Localizer\Constants::STATUS_CART_FILE_SENT,
                        \Localizationteam\Localizer\Constants::STATUS_CART_FILE_SENT,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . \Localizationteam\Localizer\Constants::STATUS_CART_TRANSLATION_IN_PROGRESS,
                        \Localizationteam\Localizer\Constants::STATUS_CART_TRANSLATION_IN_PROGRESS,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . \Localizationteam\Localizer\Constants::STATUS_CART_TRANSLATION_FINISHED,
                        \Localizationteam\Localizer\Constants::STATUS_CART_TRANSLATION_FINISHED,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . \Localizationteam\Localizer\Constants::STATUS_CART_FILE_DOWNLOADED,
                        \Localizationteam\Localizer\Constants::STATUS_CART_FILE_DOWNLOADED,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . \Localizationteam\Localizer\Constants::STATUS_CART_FILE_IMPORTED,
                        \Localizationteam\Localizer\Constants::STATUS_CART_FILE_IMPORTED,
                    ],
                    [$l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.100', '100'],
                    [$l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.-1', '-1'],
                    [$l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.-900', '-900'],
                    [$l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.-901', '-901'],
                    [$l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.-902', '-902'],
                ],
                'size' => 1,
                'maxitems' => 1,
                'readOnly' => 1,
            ],
        ],
        //will only hold value if error occured
        'previous_status' => [
            'exclude' => 1,
            'label' => $l10n . 'tx_localizer_settings_l10n_exportdata_mm.previous_status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [$l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.0', '0'],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . \Localizationteam\Localizer\Constants::STATUS_CART_ADDED,
                        \Localizationteam\Localizer\Constants::STATUS_CART_ADDED,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . \Localizationteam\Localizer\Constants::STATUS_CART_FINALIZED,
                        \Localizationteam\Localizer\Constants::STATUS_CART_FINALIZED,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . \Localizationteam\Localizer\Constants::STATUS_CART_FILE_EXPORTED,
                        \Localizationteam\Localizer\Constants::STATUS_CART_FILE_EXPORTED,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . \Localizationteam\Localizer\Constants::STATUS_CART_FILE_SENT,
                        \Localizationteam\Localizer\Constants::STATUS_CART_FILE_SENT,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . \Localizationteam\Localizer\Constants::STATUS_CART_TRANSLATION_IN_PROGRESS,
                        \Localizationteam\Localizer\Constants::STATUS_CART_TRANSLATION_IN_PROGRESS,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . \Localizationteam\Localizer\Constants::STATUS_CART_TRANSLATION_FINISHED,
                        \Localizationteam\Localizer\Constants::STATUS_CART_TRANSLATION_FINISHED,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . \Localizationteam\Localizer\Constants::STATUS_CART_FILE_DOWNLOADED,
                        \Localizationteam\Localizer\Constants::STATUS_CART_FILE_DOWNLOADED,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . \Localizationteam\Localizer\Constants::STATUS_CART_FILE_IMPORTED,
                        \Localizationteam\Localizer\Constants::STATUS_CART_FILE_IMPORTED,
                    ],
                    [$l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.100', '100'],
                    [$l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.-1', '-1'],
                    [$l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.-900', '-900'],
                    [$l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.-901', '-901'],
                    [$l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.-902', '-902'],
                ],
                'size' => 1,
                'maxitems' => 1,
                'readOnly' => 1,
            ],
        ],
        'action' => [
            'exclude' => 1,
            'label' => $l10n . 'tx_localizer_settings_l10n_exportdata_mm.action',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [$l10n . 'tx_localizer_settings_l10n_exportdata_mm.action.I.0', '0'],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.action.I.' . \Localizationteam\Localizer\Constants::ACTION_EXPORT_FILE,
                        \Localizationteam\Localizer\Constants::ACTION_EXPORT_FILE,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.action.I.' . \Localizationteam\Localizer\Constants::ACTION_SEND_FILE,
                        \Localizationteam\Localizer\Constants::ACTION_SEND_FILE,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.action.I.' . \Localizationteam\Localizer\Constants::ACTION_REQUEST_STATUS,
                        \Localizationteam\Localizer\Constants::ACTION_REQUEST_STATUS,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.action.I.' . \Localizationteam\Localizer\Constants::ACTION_DOWNLOAD_FILE,
                        \Localizationteam\Localizer\Constants::ACTION_DOWNLOAD_FILE,
                    ],
                    [
                        $l10n . 'tx_localizer_settings_l10n_exportdata_mm.action.I.' . \Localizationteam\Localizer\Constants::ACTION_IMPORT_FILE,
                        \Localizationteam\Localizer\Constants::ACTION_IMPORT_FILE,
                    ],
                ],
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'configuration' => [
            'exclude' => 1,
            'label' => $l10n . 'tx_localizer_settings_l10n_exportdata_mm.configuration',
            'config' => [
                'type' => 'text',
                'cols' => 80,
                'rows' => 2,
                'readOnly' => 1,
            ],
        ],
        'last_error' => [
            'exclude' => 1,
            'label' => $l10n . 'tx_localizer_settings.last_error',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '255',
                'eval' => 'trim',
                'readOnly' => 1,
            ],
        ],
        'deadline' => [
            'exclude' => 1,
            'label' => $l10n . 'tx_localizer_settings_l10n_exportdata_mm.deadline',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'size' => '48',
            ],
        ],
        'all_locale' => [
            'exclude' => 1,
            'label' => $l10n . 'tx_localizer_settings_l10n_exportdata_mm.all_locale',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'source_locale' => [
            'exclude' => 1,
            'label' => $l10n . 'tx_localizer_settings_l10n_exportdata_mm.source_locale',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'static_languages',
                'size' => 1,
                'maxitems' => 1,
                'MM' => 'tx_localizer_language_mm',
                'MM_match_fields' => [
                    'tablenames' => 'static_languages',
                    'source' => 'tx_localizer_settings_l10n_exportdata_mm',
                    'ident' => 'source',
                ],
                'readOnly' => 1,
            ],
        ],
        'target_locale' => [
            'exclude' => 1,
            'label' => $l10n . 'tx_localizer_settings_l10n_exportdata_mm.target_locale',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingleBox',
                'items' => [
                    [$l10n . 'tx_localizer_settings_l10n_exportdata_mm.all_languages', '0'],
                ],
                'foreign_table' => 'static_languages',
                'itemsProcFunc' => 'Localizationteam\Localizer\Backend\Cart->filterList',
                'size' => 4,
                'autoSizeMax' => 10,
                'maxitems' => 99,
                'MM' => 'tx_localizer_language_mm',
                'MM_match_fields' => [
                    'tablenames' => 'static_languages',
                    'source' => 'tx_localizer_settings_l10n_exportdata_mm',
                    'ident' => 'target',
                ],
                'readOnly' => 1,
            ],
        ],

    ],
    'types' => [
        '0' => ['showitem' => 'hidden,uid_local,uid_export,uid_foreign,description,localizer_path,deadline,action,all_locale,source_locale,target_locale,configuration,status,last_error'],
    ],
];
