<?php

declare(strict_types=1);

use Localizationteam\Localizer\Constants;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$l10n = 'LLL:EXT:localizer/Resources/Private/Language/locallang_db.xlf';

$tempColumns = [
    'tx_localizer_status' => [
        'exclude' => 1,
        'label' => $l10n . ':tx_localizer_settings_l10n_exportdata_mm.status',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [$l10n . ':tx_localizer_settings_l10n_exportdata_mm.status.I.0', '0'],
                [
                    $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_ADDED,
                    Constants::STATUS_CART_ADDED,
                ],
                [
                    $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_FINALIZED,
                    Constants::STATUS_CART_FINALIZED,
                ],
                [
                    $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_FILE_EXPORTED,
                    Constants::STATUS_CART_FILE_EXPORTED,
                ],
                [
                    $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_FILE_SENT,
                    Constants::STATUS_CART_FILE_SENT,
                ],
                [
                    $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_TRANSLATION_IN_PROGRESS,
                    Constants::STATUS_CART_TRANSLATION_IN_PROGRESS,
                ],
                [
                    $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_TRANSLATION_FINISHED,
                    Constants::STATUS_CART_TRANSLATION_FINISHED,
                ],
                [
                    $l10n . 'tx_localizer_settings_l10n_exportdata_mm.status.I.' . Constants::STATUS_CART_FILE_DOWNLOADED,
                    Constants::STATUS_CART_FILE_DOWNLOADED,
                ],
                [$l10n . ':tx_localizer_settings_l10n_exportdata_mm.status.I.-1', '-1'],
            ],
            'size' => 1,
            'maxitems' => 1,
            'readOnly' => 1,
        ],
    ],

];

$GLOBALS['TCA']['tx_l10nmgr_exportdata']['ctrl']['label'] = 'filename';

ExtensionManagementUtility::addTCAcolumns('tx_l10nmgr_exportdata', $tempColumns);
ExtensionManagementUtility::addToAllTCAtypes('tx_l10nmgr_exportdata', 'tx_localizer_status');
