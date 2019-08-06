<?php

$l10n = 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf';

$tempColumns = [
    'tx_localizer_status' => [
        'exclude' => 1,
        'label'   => $l10n . ':tx_localizer_settings_l10n_exportdata_mm.status',
        'config'  => [
            'type'       => 'select',
            'renderType' => 'selectSingle',
            'items'      => [
                [$l10n . ':tx_localizer_settings_l10n_exportdata_mm.status.I.0', '0'],
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
                [$l10n . ':tx_localizer_settings_l10n_exportdata_mm.status.I.-1', '-1'],
            ],
            'size'       => 1,
            'maxitems'   => 1,
            'readOnly'   => 1,
        ],
    ],

];
$GLOBALS['TCA']['tx_l10nmgr_exportdata']['ctrl']['label'] = 'filename';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tx_l10nmgr_exportdata', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tx_l10nmgr_exportdata', 'tx_localizer_status');