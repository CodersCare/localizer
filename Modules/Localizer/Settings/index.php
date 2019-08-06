<?php
$GLOBALS['SOBE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Localizationteam\Localizer\Controller\SettingsController::class);
$GLOBALS['SOBE']->init();
$GLOBALS['SOBE']->main();
$GLOBALS['SOBE']->printContent();
