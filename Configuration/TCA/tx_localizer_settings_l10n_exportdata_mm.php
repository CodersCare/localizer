<?php

declare(strict_types=1);

use Localizationteam\Localizer\Constants;

defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm',
        'label' => 'uid_local',
        //'label_alt' => 'uid_foreign',
        //'label_alt_force' => '1',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        // TODO: Remove when dropping support for v11
        'cruser_id' => 'cruser_id',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'ignorePageTypeRestriction' => true,
        'iconfile' => 'EXT:localizer/Resources/Public/Icons/module-localizer-cart.svg',
    ],
    'feInterface' => '',
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'uid_local' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.uid_local',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_localizer_settings',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'readOnly' => 1,
            ],
        ],
        'uid_export' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.uid_export',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_l10nmgr_exportdata',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'readOnly' => 1,
            ],
        ],
        'uid_foreign' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.uid_foreign',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_l10nmgr_cfg',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'readOnly' => 1,
            ],
        ],
        'description' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.description',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '255',
                'eval' => 'trim',
            ],
        ],
        'localizer_path' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.localizer_path',
            'config' => [
                'type' => 'text',
                'cols' => 80,
                'rows' => 2,
                'readOnly' => 1,
            ],
        ],
        'status' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.0', '0'],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_ADDED,
                        Constants::STATUS_CART_ADDED,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_FINALIZED,
                        Constants::STATUS_CART_FINALIZED,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_FILE_EXPORTED,
                        Constants::STATUS_CART_FILE_EXPORTED,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_FILE_SENT,
                        Constants::STATUS_CART_FILE_SENT,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_TRANSLATION_IN_PROGRESS,
                        Constants::STATUS_CART_TRANSLATION_IN_PROGRESS,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_TRANSLATION_FINISHED,
                        Constants::STATUS_CART_TRANSLATION_FINISHED,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_FILE_DOWNLOADED,
                        Constants::STATUS_CART_FILE_DOWNLOADED,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_FILE_IMPORTED,
                        Constants::STATUS_CART_FILE_IMPORTED,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_SUCCESS_REPORTED,
                        Constants::STATUS_CART_SUCCESS_REPORTED,
                    ],
                    ['LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.100', '100'],
                    ['LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.-1', '-1'],
                    ['LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.-900', '-900'],
                    ['LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.-901', '-901'],
                    ['LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.-902', '-902'],
                ],
                'size' => 1,
                'maxitems' => 1,
                'readOnly' => 1,
            ],
        ],
        //will only hold value if error occured
        'previous_status' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.previous_status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.0', '0'],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_ADDED,
                        Constants::STATUS_CART_ADDED,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_FINALIZED,
                        Constants::STATUS_CART_FINALIZED,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_FILE_EXPORTED,
                        Constants::STATUS_CART_FILE_EXPORTED,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_FILE_SENT,
                        Constants::STATUS_CART_FILE_SENT,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_TRANSLATION_IN_PROGRESS,
                        Constants::STATUS_CART_TRANSLATION_IN_PROGRESS,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_TRANSLATION_FINISHED,
                        Constants::STATUS_CART_TRANSLATION_FINISHED,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_FILE_DOWNLOADED,
                        Constants::STATUS_CART_FILE_DOWNLOADED,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_FILE_IMPORTED,
                        Constants::STATUS_CART_FILE_IMPORTED,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_SUCCESS_REPORTED,
                        Constants::STATUS_CART_SUCCESS_REPORTED,
                    ],
                    ['LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.100', '100'],
                    ['LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.-1', '-1'],
                    ['LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.-900', '-900'],
                    ['LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.-901', '-901'],
                    ['LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.status.I.-902', '-902'],
                ],
                'size' => 1,
                'maxitems' => 1,
                'readOnly' => 1,
            ],
        ],
        'action' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.action',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.action.I.0', '0'],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.action.I.' . Constants::ACTION_EXPORT_FILE,
                        Constants::ACTION_EXPORT_FILE,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.action.I.' . Constants::ACTION_SEND_FILE,
                        Constants::ACTION_SEND_FILE,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.action.I.' . Constants::ACTION_REQUEST_STATUS,
                        Constants::ACTION_REQUEST_STATUS,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.action.I.' . Constants::ACTION_DOWNLOAD_FILE,
                        Constants::ACTION_DOWNLOAD_FILE,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.action.I.' . Constants::ACTION_IMPORT_FILE,
                        Constants::ACTION_IMPORT_FILE,
                    ],
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.action.I.' . Constants::ACTION_REPORT_SUCCESS,
                        Constants::ACTION_REPORT_SUCCESS,
                    ],
                ],
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'configuration' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.configuration',
            'config' => [
                'type' => 'text',
                'cols' => 80,
                'rows' => 2,
                'readOnly' => 1,
            ],
        ],
        'last_error' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.last_error',
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
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.deadline',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'size' => '48',
            ],
        ],
        'all_locale' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.all_locale',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'source_locale' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.source_locale',
            'config' => [
                'type' => 'language',
                'readOnly' => 1,
            ],
        ],
        'target_locale' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings_l10n_exportdata_mm.target_locale',
            'config' => [
                'type' => 'language',
                'readOnly' => 1,
            ],
        ],

    ],
    'types' => [
        '0' => ['showitem' => 'hidden,uid_local,uid_export,uid_foreign,description,localizer_path,deadline,action,all_locale,source_locale,target_locale,configuration,status,last_error'],
    ],
];
