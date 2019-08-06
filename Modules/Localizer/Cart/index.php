<?php

use Localizationteam\Localizer\Controller\CartController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$GLOBALS['SOBE'] = GeneralUtility::makeInstance(CartController::class);
$GLOBALS['SOBE']->init();
$GLOBALS['SOBE']->main();
$GLOBALS['SOBE']->printContent();
