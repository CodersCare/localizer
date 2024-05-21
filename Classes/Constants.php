<?php

declare(strict_types=1);

namespace Localizationteam\Localizer;

/**
 * Constants for Localizer TYPO3 connector
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class Constants
{
    public const API_TRANSLATION_STATUS_TRANSLATED = 1;
    public const API_TRANSLATION_STATUS_IN_PROGRESS = 2;
    public const API_TRANSLATION_STATUS_WAITING = 3;

    public const CONNECTOR_VERSION = '8.0.0';
    public const CONNECTOR_NAME = 'TYPO3';
    public const DEADLINE_OFFSET = 86400;

    public const STATUS_CART_ADDED = 10;
    public const STATUS_CART_FINALIZED = 20;
    public const STATUS_CART_FILE_EXPORTED = 30;
    public const STATUS_CART_FILE_SENT = 40;
    public const STATUS_CART_TRANSLATION_IN_PROGRESS = 50;
    public const STATUS_CART_TRANSLATION_FINISHED = 60;
    public const STATUS_CART_FILE_DOWNLOADED = 70;
    public const STATUS_CART_FILE_IMPORTED = 80;
    public const STATUS_CART_SUCCESS_REPORTED = 90;

    public const STATUS_CART_ERROR = -1;

    public const ACTION_EXPORT_FILE = self::STATUS_CART_FINALIZED;
    public const ACTION_SEND_FILE = self::STATUS_CART_FILE_EXPORTED;
    public const ACTION_REQUEST_STATUS = self::STATUS_CART_FILE_SENT;
    public const ACTION_DOWNLOAD_FILE = self::STATUS_CART_TRANSLATION_FINISHED;
    public const ACTION_IMPORT_FILE = self::STATUS_CART_FILE_DOWNLOADED;
    public const ACTION_REPORT_SUCCESS = self::STATUS_CART_FILE_IMPORTED;

    public const HANDLER_FILEEXPORTER_START = self::STATUS_CART_FINALIZED;
    public const HANDLER_FILEEXPORTER_FINISH = self::STATUS_CART_FILE_EXPORTED;
    public const HANDLER_FILEEXPORTER_MAX_FILES = 2147483647;
    public const HANDLER_FILEEXPORTER_ERROR_STATUS_RESET = self::STATUS_CART_FINALIZED;
    public const HANDLER_FILEEXPORTER_ERROR_ACTION_RESET = self::ACTION_EXPORT_FILE;

    public const HANDLER_FILESENDER_START = self::HANDLER_FILEEXPORTER_FINISH;
    public const HANDLER_FILESENDER_FINISH = self::STATUS_CART_FILE_SENT;
    public const HANDLER_FILESENDER_MAX_FILES = 2147483647;

    public const HANDLER_STATUSREQUESTER_START = self::HANDLER_FILESENDER_FINISH;
    public const HANDLER_STATUSREQUESTER_FINISH = self::STATUS_CART_TRANSLATION_FINISHED;
    public const HANDLER_STATUSREQUESTER_MAX_FILES = 2147483647;
    public const HANDLER_STATUSREQUESTER_ERROR_STATUS_RESET = self::STATUS_CART_FILE_SENT;
    public const HANDLER_STATUSREQUESTER_ERROR_ACTION_RESET = self::ACTION_REQUEST_STATUS;

    public const HANDLER_FILEDOWNLOADER_START = self::HANDLER_STATUSREQUESTER_FINISH;
    public const HANDLER_FILEDOWNLOADER_FINISH = self::STATUS_CART_FILE_DOWNLOADED;
    public const HANDLER_FILEDOWNLOADER_MAX_FILES = 2147483647;
    public const HANDLER_FILEDOWNLOADER_ERROR_STATUS_RESET = self::STATUS_CART_FILE_SENT;
    public const HANDLER_FILEDOWNLOADER_ERROR_ACTION_RESET = self::ACTION_REQUEST_STATUS;

    public const HANDLER_FILEIMPORTER_START = self::HANDLER_FILEDOWNLOADER_FINISH;
    public const HANDLER_FILEIMPORTER_FINISH = self::STATUS_CART_FILE_IMPORTED;
    public const HANDLER_FILEIMPORTER_MAX_FILES = 2147483647;

    public const HANDLER_SUCCESSREPORTER_START = self::HANDLER_FILEIMPORTER_FINISH;
    public const HANDLER_SUCCESSREPORTER_FINISH = self::STATUS_CART_SUCCESS_REPORTED;

    public const TABLE_BACKEND_USERS = 'be_users';
    public const TABLE_LOCALIZER_CART = 'tx_localizer_cart';
    public const TABLE_CARTDATA_MM = 'tx_localizer_cart_table_record_language_mm';
    public const TABLE_EXPORTDATA_MM = 'tx_localizer_settings_l10n_exportdata_mm';
    public const TABLE_LOCALIZER_SETTINGS = 'tx_localizer_settings';
    public const TABLE_LOCALIZER_SETTINGS_PAGES_MM = 'tx_localizer_settings_pages_mm';
    public const TABLE_LOCALIZER_LANGUAGE_MM = 'tx_localizer_language_mm';
    public const TABLE_LOCALIZER_L10NMGR_MM = 'tx_localizer_settings_l10n_cfg_mm';
    public const TABLE_STATIC_LANGUAGES = 'static_languages';
    public const TABLE_SYS_LANGUAGE = 'sys_language';
    public const TABLE_L10NMGR_CONFIGURATION = 'tx_l10nmgr_cfg';
    public const TABLE_L10NMGR_INDEX = 'tx_l10nmgr_index';
    public const TABLE_L10NMGR_EXPORTDATA = 'tx_l10nmgr_exportdata';
}
