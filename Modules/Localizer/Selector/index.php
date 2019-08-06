<?php

use Localizationteam\Localizer\Controller\SelectorController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$GLOBALS['SOBE'] = GeneralUtility::makeInstance(SelectorController::class);
$GLOBALS['SOBE']->init();
$GLOBALS['SOBE']->main();
$GLOBALS['SOBE']->printContent();
