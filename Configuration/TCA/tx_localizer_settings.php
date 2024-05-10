<?php

declare(strict_types=1);

defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'type' => 'type',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:localizer/Resources/Public/Icons/module-localizer-settings.svg',
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
        'type' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.type.I.0', '0'],
                ],
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.title',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '255',
                'eval' => 'required,trim',
            ],
        ],
        'description' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.description',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'url' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.url',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '255',
                'checkbox' => '',
                'eval' => 'trim,nospace',
            ],
        ],
        'out_folder' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.folder.out',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '255',
                'checkbox' => '',
                'eval' => 'trim,nospace',
            ],
        ],
        'in_folder' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.folder.in',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '255',
                'checkbox' => '',
                'eval' => 'trim,nospace',
            ],
        ],
        'workflow' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.workflow',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '255',
                'eval' => 'trim',
            ],
        ],
        'projectkey' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.projectkey',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '255',
                'eval' => 'trim',
            ],
        ],
        'username' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.username',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '255',
                'eval' => 'trim,nospace',
            ],
        ],
        'password' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.password',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '255',
                'eval' => 'trim,password',
            ],
        ],
        'project_settings' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.project_settings',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'readOnly' => 1,
            ],
        ],
        'deadline' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.deadline',
            'config' => [
                'type' => 'check',
                'default' => '0',
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
        'l10n_cfg' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.l10n_cfg',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_l10nmgr_cfg',
                'foreign_table_where' => 'ORDER BY tx_l10nmgr_cfg.title',
                'size' => 5,
                'autoSizeMax' => 10,
                'minitems' => 0,
                'maxitems' => 99,
                'MM' => 'tx_localizer_settings_l10n_cfg_mm',
                'fieldControl' => [
                    'addRecord' => [
                        'disabled' => false,
                    ],
                    'listModule' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        'sortexports' => [
            'exclude' => 1,
            'label'   => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.l10nmgr_sortexports',
            'config'  => [
                'type'    => 'check',
                'default' => 1,
            ],
        ],
        'plainxmlexports' => [
            'exclude' => 1,
            'label'   => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.l10nmgr_plainxmlexports',
            'config'  => [
                'type'    => 'check',
                'default' => 0,
            ],
        ],
        'allow_adding_to_export' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.allow_adding_to_export',
            'config' => [
                'type' => 'check',
                'items' => [
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.allow_adding_to_export.allow',
                        '',
                    ],
                ],
            ],
        ],
        'collect_pages_marked_for_export' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.collect_pages_marked_for_export',
            'config' => [
                'type' => 'check',
                'items' => [
                    [
                        'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.collect_pages_marked_for_export.collect',
                        '',
                    ],
                ],
            ],
        ],
        'automatic_export_minimum_age' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.automatic_export_minimum_age',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'eval' => 'int',
            ],
        ],
        'automatic_export_pages' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.automatic_export_pages',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'pages',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 2,
                'autoSizeMax' => 5,
                'MM' => 'tx_localizer_settings_pages_mm',
                'readOnly' => 1,
            ],
        ],
        'source_locale' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.source_locale',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'static_languages',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 2,
                'autoSizeMax' => 5,
                'minitems' => 1,
                'maxitems' => 99,
                'MM' => 'tx_localizer_language_mm',
                'MM_match_fields' => [
                    'tablenames' => 'static_languages',
                    'source' => 'tx_localizer_settings',
                    'ident' => 'source',
                ],
            ],
        ],
        'target_locale' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf:tx_localizer_settings.target_locale',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'static_languages',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 3,
                'autoSizeMax' => 10,
                'minitems' => 1,
                'maxitems' => 99,
                'MM' => 'tx_localizer_language_mm',
                'MM_match_fields' => [
                    'tablenames' => 'static_languages',
                    'source' => 'tx_localizer_settings',
                    'ident' => 'target',
                ],
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'hidden, --palette--;;1, type, title, description, out_folder, in_folder, workflow, deadline, projectkey, --palette--;;2, --palette--;;3, l10n_cfg, --palette--;;4, source_locale, target_locale'],
    ],
    'palettes' => [
        '1' => ['showitem' => 'project_settings,last_error'],
        '2' => ['showitem' => 'automatic_export_pages,allow_adding_to_export'],
        '3' => ['showitem' => 'automatic_export_minimum_age,collect_pages_marked_for_export'],
        '4' => ['showitem' => 'sortexports, plainxmlexports'],
    ],
];
