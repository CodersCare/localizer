<?php
$GLOBALS['SOBE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Localizationteam\Localizer\Controller\SelectorController::class);
$GLOBALS['SOBE']->init();
$GLOBALS['SOBE']->main();
$GLOBALS['SOBE']->printContent();
