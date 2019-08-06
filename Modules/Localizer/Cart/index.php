<?php
$GLOBALS['SOBE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Localizationteam\Localizer\Controller\CartController::class);
$GLOBALS['SOBE']->init();
$GLOBALS['SOBE']->main();
$GLOBALS['SOBE']->printContent();
