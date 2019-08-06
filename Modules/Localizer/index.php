<?php

use Localizationteam\Localizer\Controller\LocalizerController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$GLOBALS['SOBE'] = GeneralUtility::makeInstance(LocalizerController::class);
$GLOBALS['SOBE']->main();
$GLOBALS['SOBE']->printContent();
