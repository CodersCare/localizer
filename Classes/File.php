<?php

namespace Localizationteam\Localizer;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * File
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
trait File
{
    /**
     * @param string $fileName
     * @param string $locale
     * @return string
     * @throws FolderDoesNotExistException
     */
    protected function getLocalFilename($fileName, $locale)
    {
        $downloadPath = Environment::getPublicPath() . '/uploads/tx_l10nmgr/jobs/in/' . strtolower($locale);
        if (!@is_dir($downloadPath)) {
            GeneralUtility::mkdir_deep($downloadPath);
        }
        return $downloadPath . '/' . $fileName;
    }
}
