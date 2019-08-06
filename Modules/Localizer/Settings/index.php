<?php

use Localizationteam\Localizer\Controller\SettingsController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$GLOBALS['SOBE'] = GeneralUtility::makeInstance(SettingsController::class);
$GLOBALS['SOBE']->init();
$GLOBALS['SOBE']->main();
$GLOBALS['SOBE']->printContent();
