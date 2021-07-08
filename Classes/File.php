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
     * @var string
     */
    protected $downloadPath = '';

    /**
     * @param string $fileName
     * @param string $locale
     * @return string
     * @throws FolderDoesNotExistException
     */
    protected function getLocalFilename($fileName, $locale)
    {
        if ($this->downloadPath === '') {
            $this->downloadPath = Environment::getPublicPath() . '/uploads/tx_l10nmgr/jobs/in/';
        }
        $path = $this->downloadPath . strtolower($locale);
        if (!@is_dir($path)) {
            GeneralUtility::mkdir_deep($path);
        }
        return $path . $fileName;
    }
}
