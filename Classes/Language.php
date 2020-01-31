<?php

namespace Localizationteam\Localizer;

use Exception;
use TYPO3\CMS\Typo3DbLegacy\Database\DatabaseConnection;

/**
 * Language
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 *
 * @method DatabaseConnection getDatabaseConnection() must be define in implementing class
 *
 */
trait Language
{
    /**
     * @param string $locale
     * @return string
     * @throws Exception
     */
    protected function getIso2ForLocale($locale)
    {
        $iso2 = '';
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'lg_iso_2',
            Constants::TABLE_STATIC_LANGUAGES,
            'lg_collate_locale LIKE ("%' . str_replace('-', '%', $locale) . '%")'
        );
        if ($row) {
            if (isset($row['lg_iso_2'])) {
                $iso2 = trim($row['lg_iso_2']);
            }
        }
        if ($iso2 === '') {
            throw new Exception($locale . ' can not be found in TYPO3 "static_languages". Please inform your admin!');
        }
        return $iso2;
    }

    /**
     * @param array $row
     * @return bool
     */
    protected function translateAll(array &$row)
    {
        $translateAll = false;
        if (isset($row['all_locale'])) {
            $translateAll = (bool)$row['all_locale'];
        }
        return $translateAll;
    }

    /**
     * @param int $uidLocal
     * @param $table
     * @return array
     */
    protected function getAllTargetLanguageUids($uidLocal, $table)
    {
        $languageUids = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid_foreign',
            Constants::TABLE_LOCALIZER_LANGUAGE_MM,
            'uid_local = ' . $uidLocal .
            ' AND tablenames = "static_languages" AND source = "' . $table . '" AND ident ="target"',
            '',
            '',
            '',
            'uid_foreign'
        );
        return array_keys($languageUids);
    }

    /**
     * @param array $uidList
     * @param bool $fixUnderLine
     * @return array
     */
    protected function getStaticLanguagesCollateLocale(array $uidList, $fixUnderLine = false)
    {
        $collateLocale = [];
        if (count($uidList) > 0) {
            $field = 'lg_collate_locale';
            $orgField = $field;
            if ((bool)$fixUnderLine === true) {
                $field = 'REPLACE(' . $field . ', "_", "-") as ' . $field;
            }
            $rows = $this->getDatabaseConnection()
                ->exec_SELECTgetRows(
                    $field,
                    Constants::TABLE_STATIC_LANGUAGES,
                    'uid IN (' . join(',', $uidList) . ')',
                    '',
                    '',
                    '',
                    $orgField
                );
            $collateLocale = array_keys($rows);
        }
        return $collateLocale;
    }
}