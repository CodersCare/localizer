<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Upgrades;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Traits\ConnectionPoolTrait;
use PDO;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('localizer_languagesUpgradeWizard')]
final class LanguagesUpgradeWizard implements UpgradeWizardInterface, ChattyInterface
{

    use ConnectionPoolTrait;

    private OutputInterface $output;

    public function getTitle() : string
    {
        return 'Language Upgrade wizard';
    }

    public function getDescription() : string
    {
        return 'Updates the language IDs from static_language to SiteLanguage';
    }

    public function executeUpdate(): bool
    {
        $result = false;

        $localizerSettings = $this->getLocalizerSettings();

        // Return early when no localizer settings found
        if (empty($localizerSettings)) {
            $this->output->writeln('No Localizer Settings found');

            return true;
        }

        $staticLanguages = $this->getStaticLanguages();

        foreach ($localizerSettings as $localizerSetting) {
            $siteLanguages = $this->getSiteLanguagesForSite($localizerSetting['pid']);

            // Move to the next Localizer setting when no SiteLanguages for the current site found
            if (empty($siteLanguages)) {
                $this->output->writeln('No site languages for the current site found.');
                continue;
            }

            ['source' => $localizerStaticSourceLanguageId, 'target' => $localizerStaticTargetLanguageId] = $this->getLocalizerLanguages($localizerSetting['uid']);

            if ((int)$localizerStaticSourceLanguageId > 0) {
                $staticIsoCode = $staticLanguages[$localizerStaticSourceLanguageId]['lg_iso_2'];

                if (isset($siteLanguages[$staticIsoCode])) {
                    $siteLangId = $siteLanguages[$staticIsoCode]['languageId'];
                } else {
                    $this->output->writeln('No SiteLanguage found which matches the static languages iso code: ' . $staticIsoCode);
                }
            } else {
                $this->output->writeln('The source language found in localizer settings with ID ' . $localizerSetting['uid'] . ' is not valid.');
            }

            $newIds = '';
            if ($localizerStaticTargetLanguageId !== '' && $localizerStaticTargetLanguageId !== null) {
                $uids = GeneralUtility::intExplode(',', $localizerStaticTargetLanguageId);

                $newIds = [];
                foreach ($uids as $uid) {
                    $staticIsoCode = $staticLanguages[$uid]['lg_iso_2'];

                    if (isset($siteLanguages[$staticIsoCode])) {
                        $newIds[] = $siteLanguages[$staticIsoCode]['languageId'];
                    } else {
                        $this->output->writeln('No SiteLanguage found which matches the static languages iso code: ' . $staticIsoCode);
                    }
                }

                $newIds = implode(',', $newIds);
                $result = true;
            } else {
                $this->output->writeln('No target languages found in localizer settings with ID ' . $localizerSetting['uid'] . '.');
            }

            self::getConnectionPool()
                ->getConnectionForTable(Constants::TABLE_LOCALIZER_SETTINGS)
                ->update(Constants::TABLE_LOCALIZER_SETTINGS, ['source_language' => $siteLangId, 'target_languages' => $newIds], ['uid' => $localizerSetting['uid']]);
        }

        return $result;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws DBALException
     * @throws Exception
     */
    public function updateNecessary() : bool
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_SETTINGS);
        $localizerSettings = $queryBuilder
                    ->select('*')
                    ->from(Constants::TABLE_LOCALIZER_SETTINGS, 'settings')
                    ->executeQuery()
                    ->fetchAllAssociative();

        $result = false;
        foreach ($localizerSettings as $localizerSetting) {
            if ($localizerSetting['target_languages'] === '') {
                $result = true;
            }
        }

        //return $result;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier() : string
    {
        return 'localizer_languagesUpgradeWizard';
    }

    /**
     * @inheritDoc
     */
    public function getPrerequisites() : array
    {
        return [];
    }

    protected function getStaticLanguages(): array
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_STATIC_LANGUAGES);
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
                    ->select('uid', 'lg_collate_locale', 'lg_name_en', 'lg_iso_2')
                    ->from(Constants::TABLE_STATIC_LANGUAGES)
                    ->where($queryBuilder->expr()->neq('lg_collate_locale', "''"))
                    ->executeQuery()
                    ->fetchAllAssociative();

        $staticLanguages = [];
        foreach ($result as $item) {
            $item['lg_name_en'] = strtolower($item['lg_name_en']);
            $item['lg_iso_2'] = strtolower($item['lg_iso_2']);
            $staticLanguages[$item['uid']] = $item;
        }

        return $staticLanguages;
    }

    protected function getLocalizerSettings(): array
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_SETTINGS);
        return $queryBuilder
                    ->select('*')
                    ->from(Constants::TABLE_LOCALIZER_SETTINGS, 'settings')
                    ->executeQuery()
                    ->fetchAllAssociative();
    }

    public function getLocalizerLanguages(int $localizerId): array
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_SETTINGS);
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->selectLiteral('MAX(sourceLanguage.uid) source, GROUP_CONCAT(targetLanguage.uid) target')
            ->from(Constants::TABLE_LOCALIZER_SETTINGS, 'settings')
            ->leftJoin(
                'settings',
                Constants::TABLE_LOCALIZER_LANGUAGE_MM,
                'sourceMM',
                (string) $queryBuilder
                    ->expr()
                    ->and(
                        $queryBuilder->expr()->eq('settings.uid', $queryBuilder->quoteIdentifier('sourceMM.uid_local')),
                        $queryBuilder->expr()->eq('sourceMM.tablenames', $queryBuilder->createNamedParameter(Constants::TABLE_STATIC_LANGUAGES, PDO::PARAM_STR)),
                        $queryBuilder->expr()->eq('sourceMM.ident', $queryBuilder->createNamedParameter('source', PDO::PARAM_STR)),
                        $queryBuilder->expr()->eq('sourceMM.source', $queryBuilder->createNamedParameter(Constants::TABLE_LOCALIZER_SETTINGS, PDO::PARAM_STR))
                    )
            )
            ->leftJoin(
                'sourceMM',
                Constants::TABLE_STATIC_LANGUAGES,
                'sourceLanguage',
                $queryBuilder
                    ->expr()
                    ->eq('sourceLanguage.uid', $queryBuilder->quoteIdentifier('sourceMM.uid_foreign'))
            )
            ->leftJoin(
                'settings',
                Constants::TABLE_LOCALIZER_LANGUAGE_MM,
                'targetMM',
                (string) $queryBuilder
                    ->expr()
                    ->and(
                        $queryBuilder->expr()->eq('settings.uid', $queryBuilder->quoteIdentifier('targetMM.uid_local')),
                        $queryBuilder->expr()->eq('targetMM.tablenames', $queryBuilder->createNamedParameter(Constants::TABLE_STATIC_LANGUAGES, PDO::PARAM_STR)),
                        $queryBuilder->expr()->eq('targetMM.ident', $queryBuilder->createNamedParameter('target', PDO::PARAM_STR)),
                        $queryBuilder->expr()->eq('targetMM.source', $queryBuilder->createNamedParameter(Constants::TABLE_LOCALIZER_SETTINGS, PDO::PARAM_STR))
                    )
            )
            ->leftJoin(
                'targetMM',
                Constants::TABLE_STATIC_LANGUAGES,
                'targetLanguage',
                $queryBuilder
                    ->expr()
                    ->eq('targetLanguage.uid', $queryBuilder->quoteIdentifier('targetMM.uid_foreign'))
            )
            ->where(
                $queryBuilder->expr()->eq('settings.uid', $localizerId)
            )
            ->groupBy('settings.uid')
            ->executeQuery()
            ->fetchAssociative();

        return (array) $result;
    }

    private function getSiteByPageId(int $pageId): Site
    {
        /** @var SiteFinder $siteFinder */
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        return $siteFinder->getSiteByPageId($pageId);
    }

    /**
     * @param int $pageId
     * @return SiteLanguage[]
     */
    private function getSiteLanguagesForSite(int $pageId): array
    {
        /** @var SiteLanguage[] $allLanguage */
        $allLanguage = $this->getSiteByPageId($pageId)->getAllLanguages();
        $siteLanguagesByLocale = [];
        foreach ($allLanguage as $language) {
            $siteLanguagesByLocale[strtolower($language->getTwoLetterIsoCode())] = $language->toArray();
        }

        return $siteLanguagesByLocale;
    }

    public function setOutput(OutputInterface $output) : void
    {
        $this->output = $output;
    }
}
