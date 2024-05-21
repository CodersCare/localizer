<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Model\Repository;

use Doctrine\DBAL\Connection as ConnectionAlias;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Localizationteam\Localizer\Constants;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LanguageRepository extends AbstractRepository
{

    public function __construct(
        public readonly SiteFinder $siteFinder,
    ) {
        parent::__construct();
    }

    /**
     * @throws SiteNotFoundException
     */
    public function getIsoTwoCodeBySystemLanguageId(int $localeId, int $pid = 1): string
    {
        $site = $this->siteFinder->getSiteByPageId($pid);
        $languages = $site->getAllLanguages();

        if (($languages[$localeId] ?? null) instanceof SiteLanguage) {
            return $languages[$localeId]->getHreflang();
        }

        return '';
    }

    /**
     * @param int $pageId
     * @return SiteLanguage[]
     * @throws SiteNotFoundException
     */
    public function getStaticLanguages(int $pageId): array
    {
        return $this->siteFinder->getSiteByPageId($pageId)->getAvailableLanguages($this->getBackendUser());
    }

    /**
     * @throws Exception
     * @throws DBALException
     * @throws \Doctrine\DBAL\Exception
     */
    public function getAllTargetLanguageUids(int $uidLocal, string $table): array
    {
        $queryBuilder = self::getConnectionPool()
            ->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_LANGUAGE_MM);
        $queryBuilder->getRestrictions()->removeAll();

        $rows = $queryBuilder
            ->select('uid_foreign')
            ->from(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $uidLocal
                    ),
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter(Constants::TABLE_STATIC_LANGUAGES, Connection::PARAM_STR)
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
            ->executeQuery()
            ->fetchAllAssociative();

        $languageUids = [];
        if (!empty($rows)) {
            $languageUids = array_column($rows, 'uid_foreign');
        }
        return $languageUids;
    }

    public function getStaticLanguagesCollateLocale(array $uidList, bool $fixUnderLine = false): array
    {
        $collateLocale = [];
        if (count($uidList) > 0) {
            $field = 'lg_collate_locale';
            $orgField = $field;
            $uidList = GeneralUtility::intExplode(',', implode(',', $uidList), true);
            if ($fixUnderLine === true) {
                $field = 'REPLACE(' . $field . ', "_", "-") as ' . $field;
            }
            $queryBuilder = self::getConnectionPool()
                ->getQueryBuilderForTable(Constants::TABLE_STATIC_LANGUAGES);
            $queryBuilder->getRestrictions()
                ->removeAll();
            $result = $queryBuilder
                ->selectLiteral($field)
                ->from(Constants::TABLE_STATIC_LANGUAGES)
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter($uidList, ConnectionAlias::PARAM_INT_ARRAY)
                    )
                )
                ->executeQuery();
            $rows = $this->fetchAllAssociative($result);
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

    public function getSystemLanguageIdByTargetLanguage(int $uid): int
    {
        if ($uid <= 0) {
            return 0;
        }

        if ($this->typo3Version->getMajorVersion() < 12) {
            $queryBuilder = self::getConnectionPool()
                ->getQueryBuilderForTable(Constants::TABLE_SYS_LANGUAGE);
            $queryBuilder->getRestrictions()->removeAll();
            $result = $queryBuilder
                ->select('uid')
                ->from(Constants::TABLE_SYS_LANGUAGE)
                ->where(
                    $queryBuilder->expr()->eq(
                        'static_lang_isocode',
                        $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                    )
                )
                ->executeQuery();

            return (int)$this->fetchOne($result);
        } else {
            $queryBuilder = self::getConnectionPool()
                ->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_SETTINGS);
            $queryBuilder->getRestrictions()->removeAll();
            $result = $queryBuilder
                ->select('uid')
                ->from(Constants::TABLE_LOCALIZER_SETTINGS)
                ->where(
                    $queryBuilder->expr()->eq(
                        'static_lang_isocode',
                        $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                    )
                )
                ->executeQuery();
        }
    }
}
