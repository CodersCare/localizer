<?php

namespace Localizationteam\Localizer;

use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;

/**
 * File $COMMENT$
 *
 * @author      Peter Russ<peter.russ@4many.net>
 * @package     TYPO3
 * @date        20150924-1133
 * @subpackage  localizer
 *
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
            $this->downloadPath = PATH_site . 'uploads/tx_l10nmgr/jobs/in/';
        }
        //$path = $this->downloadPath . strtolower($locale) . '/';
        $path = $this->downloadPath;
        if (file_exists($path) === false) {
            //GeneralUtility::mkdir_deep(PATH_site . 'uploads/', 'tx_l10nmgr/jobs/in/' . strtolower($locale));
            if (file_exists($path) === false) {
                throw new FolderDoesNotExistException('Can not create folder uploads/tx_l10nmgr/jobs/in/' . strtolower($locale));
            }
        } else {
            if (is_dir($path) === false) {
                throw new FolderDoesNotExistException('Path uploads/tx_l10nmgr/jobs/in/' . strtolower($locale) . ' exists but is not a folder');
            }
        }
        return $path . $fileName;
    }
}