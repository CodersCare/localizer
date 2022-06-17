<?php

namespace Localizationteam\Localizer;

/**
 * Constants for Localizer TYPO3 connector
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class Constants
{
    const API_TRANSLATION_STATUS_TRANSLATED = 1;
    const API_TRANSLATION_STATUS_IN_PROGRESS = 2;
    const API_TRANSLATION_STATUS_WAITING = 3;

    const CONNECTOR_VERSION = '8.0.0';
    const CONNECTOR_NAME = 'TYPO3';
    const DEADLINE_OFFSET = 86400;

    const STATUS_CART_ADDED = 10;
    const STATUS_CART_FINALIZED = 20;
    const STATUS_CART_FILE_EXPORTED = 30;
    const STATUS_CART_FILE_SENT = 40;
    const STATUS_CART_TRANSLATION_IN_PROGRESS = 50;
    const STATUS_CART_TRANSLATION_FINISHED = 60;
    const STATUS_CART_FILE_DOWNLOADED = 70;
    const STATUS_CART_FILE_IMPORTED = 80;
    const STATUS_CART_SUCCESS_REPORTED = 90;

    const STATUS_CART_ERROR = -1;

    const ACTION_EXPORT_FILE = self::STATUS_CART_FINALIZED;
    const ACTION_SEND_FILE = self::STATUS_CART_FILE_EXPORTED;
    const ACTION_REQUEST_STATUS = self::STATUS_CART_FILE_SENT;
    const ACTION_DOWNLOAD_FILE = self::STATUS_CART_TRANSLATION_FINISHED;
    const ACTION_IMPORT_FILE = self::STATUS_CART_FILE_DOWNLOADED;
    const ACTION_REPORT_SUCCESS = self::STATUS_CART_FILE_IMPORTED;

    const HANDLER_FILEEXPORTER_START = self::STATUS_CART_FINALIZED;
    const HANDLER_FILEEXPORTER_FINISH = self::STATUS_CART_FILE_EXPORTED;
    const HANDLER_FILEEXPORTER_MAX_FILES = 2147483647;
    const HANDLER_FILEEXPORTER_ERROR_STATUS_RESET = self::STATUS_CART_FINALIZED;
    const HANDLER_FILEEXPORTER_ERROR_ACTION_RESET = self::ACTION_EXPORT_FILE;

    const HANDLER_FILESENDER_START = self::HANDLER_FILEEXPORTER_FINISH;
    const HANDLER_FILESENDER_FINISH = self::STATUS_CART_FILE_SENT;
    const HANDLER_FILESENDER_MAX_FILES = 2147483647;

    const HANDLER_STATUSREQUESTER_START = self::HANDLER_FILESENDER_FINISH;
    const HANDLER_STATUSREQUESTER_FINISH = self::STATUS_CART_TRANSLATION_FINISHED;
    const HANDLER_STATUSREQUESTER_MAX_FILES = 2147483647;
    const HANDLER_STATUSREQUESTER_ERROR_STATUS_RESET = self::STATUS_CART_FILE_SENT;
    const HANDLER_STATUSREQUESTER_ERROR_ACTION_RESET = self::ACTION_REQUEST_STATUS;

    const HANDLER_FILEDOWNLOADER_START = self::HANDLER_STATUSREQUESTER_FINISH;
    const HANDLER_FILEDOWNLOADER_FINISH = self::STATUS_CART_FILE_DOWNLOADED;
    const HANDLER_FILEDOWNLOADER_MAX_FILES = 2147483647;
    const HANDLER_FILEDOWNLOADER_ERROR_STATUS_RESET = self::STATUS_CART_FILE_SENT;
    const HANDLER_FILEDOWNLOADER_ERROR_ACTION_RESET = self::ACTION_REQUEST_STATUS;

    const HANDLER_FILEIMPORTER_START = self::HANDLER_FILEDOWNLOADER_FINISH;
    const HANDLER_FILEIMPORTER_FINISH = self::STATUS_CART_FILE_IMPORTED;
    const HANDLER_FILEIMPORTER_MAX_FILES = 2147483647;

    const HANDLER_SUCCESSREPORTER_START = self::HANDLER_FILEIMPORTER_FINISH;
    const HANDLER_SUCCESSREPORTER_FINISH = self::STATUS_CART_SUCCESS_REPORTED;

    const TABLE_BACKEND_USERS = 'be_users';
    const TABLE_LOCALIZER_CART = 'tx_localizer_cart';
    const TABLE_CARTDATA_MM = 'tx_localizer_cart_table_record_language_mm';
    const TABLE_EXPORTDATA_MM = 'tx_localizer_settings_l10n_exportdata_mm';
    const TABLE_LOCALIZER_SETTINGS = 'tx_localizer_settings';
    const TABLE_LOCALIZER_SETTINGS_PAGES_MM = 'tx_localizer_settings_pages_mm';
    const TABLE_LOCALIZER_LANGUAGE_MM = 'tx_localizer_language_mm';
    const TABLE_LOCALIZER_L10NMGR_MM = 'tx_localizer_settings_l10n_cfg_mm';
    const TABLE_STATIC_LANGUAGES = 'static_languages';
    const TABLE_SYS_LANGUAGE = 'sys_language';
    const TABLE_L10NMGR_CONFIGURATION = 'tx_l10nmgr_cfg';
    const TABLE_L10NMGR_EXPORTDATA = 'tx_l10nmgr_exportdata';
}
