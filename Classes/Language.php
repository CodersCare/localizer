<?php

namespace Localizationteam\Localizer;

use Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Language
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_STATIC_LANGUAGES
        );
        $queryBuilder->getRestrictions()
            ->removeAll();
        $row = $queryBuilder
            ->select('lg_iso_2')
            ->from(Constants::TABLE_STATIC_LANGUAGES)
            ->where(
                $queryBuilder->expr()->like(
                    'lg_collate_locale',
                    $queryBuilder->createNamedParameter(
                        '%' .
                        $queryBuilder->escapeLikeWildcards(
                            str_replace(
                                '-',
                                '%',
                                $locale
                            )
                        ) .
                        '%'
                    )
                )
            )
            ->execute()
            ->fetchAssociative();
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_LOCALIZER_LANGUAGE_MM
        );
        $queryBuilder->getRestrictions()
            ->removeAll();
        $rows = $queryBuilder
            ->select('uid_foreign')
            ->from(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        (int)$uidLocal
                    ),
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter('static_languages', Connection::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'source',
                        $queryBuilder->createNamedParameter($table, Connection::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'ident',
                        $queryBuilder->createNamedParameter('target', Connection::PARAM_STR)
                    )
                )
            )
            ->execute()
            ->fetchAllAssociative();
        $languageUids = [];
        if (!empty($rows)) {
            $languageUids = array_column($rows, 'uid_foreign');
        }
        return $languageUids;
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
            $uidList = GeneralUtility::intExplode(',', implode(',', $uidList), true);
            if ((bool)$fixUnderLine === true) {
                $field = 'REPLACE(' . $field . ', "_", "-") as ' . $field;
            }
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
                Constants::TABLE_STATIC_LANGUAGES
            );
            $queryBuilder->getRestrictions()
                ->removeAll();
            $rows = $queryBuilder
                ->selectLiteral($field)
                ->from(Constants::TABLE_STATIC_LANGUAGES)
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter($uidList, Connection::PARAM_INT_ARRAY)
                    )
                )
                ->execute()
                ->fetchAllAssociative();
            if (!empty($rows)) {
                $locale = [];
                foreach ($rows as $row) {
                    if (isset($row[$orgField])) {
                        $locale[$row[$orgField]] = $row;
                    }
                }
                $collateLocale = array_keys($locale);
            }
        }
        return $collateLocale;
    }
}
