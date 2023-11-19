<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Hooks;

use Localizationteam\L10nmgr\View\PostSaveInterface;
use Localizationteam\Localizer\AddFileToMatrix;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Data;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * L10nMgrExportHandler
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class L10nMgrExportHandler implements PostSaveInterface
{
    use AddFileToMatrix;
    use Data;

    public function postExportAction(array $params): void
    {
        // XML
        if (empty($params) || empty($params['data'])) {
            return;
        }

        // XML
        if ((int)($params['data']['exportType'] ?? null) !== 1) {
            return;
        }

        if (($params['data']['source_lang'] ?? null) == ($params['data']['translation_lang'] ?? null)) {
            return;
        }

        if (($_REQUEST['export_xml_forcepreviewlanguage'] ?? null) == ($_REQUEST['SET']['lang'] ?? null)) {
            return;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_SETTINGS);
        $result = $queryBuilder
            ->select(
                Constants::TABLE_LOCALIZER_SETTINGS . '.uid',
                Constants::TABLE_LOCALIZER_SETTINGS . '.pid',
                Constants::TABLE_LOCALIZER_SETTINGS . '.project_settings',
                'source_locale',
                'target_locale'
            )
            ->from(Constants::TABLE_LOCALIZER_SETTINGS)
            ->join(
                Constants::TABLE_LOCALIZER_SETTINGS,
                Constants::TABLE_LOCALIZER_L10NMGR_MM,
                'mm'
            )
            ->join(
                'mm',
                Constants::TABLE_L10NMGR_CONFIGURATION,
                Constants::TABLE_L10NMGR_CONFIGURATION
            )
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        Constants::TABLE_LOCALIZER_SETTINGS . '.uid',
                        $queryBuilder->quoteIdentifier('mm.uid_local')
                    ),
                    $queryBuilder->expr()->eq(
                        Constants::TABLE_L10NMGR_CONFIGURATION . '.uid',
                        $queryBuilder->quoteIdentifier('mm.uid_foreign')
                    ),
                    $queryBuilder->expr()->eq(
                        'mm.uid_foreign',
                        (int)($params['data']['l10ncfg_id'] ?? null)
                    ),
                    $queryBuilder->expr()->in(
                        Constants::TABLE_LOCALIZER_SETTINGS . '.pid',
                        $this->getRootline(
                            $this->getSrcPid()
                        )
                    )
                )
            )
            ->setMaxResults(1)
            ->execute();
        $row = $this->fetchAssociative($result);
        if (!empty($row['pid'])) {
            $this->addFileToMatrix(
                $row['pid'],
                $row['uid'] ?? 0,
                $params['uid'] ?? 0,
                $params['data']['l10ncfg_id'] ?? 0,
                $params['data']['filename'] ?? '',
                $params['data']['translation_lang'] ?? 0
            );
        }
    }

    /**
     * @param int $uid
     * @return array
     */
    protected function getRootline(int $uid): array
    {
        $rootLineList = BackendUtility::BEgetRootLine($uid);
        $rootLine = [];
        foreach ($rootLineList as $page) {
            $rootLine[] = $page['uid'];
        }
        unset($rootLineList);
        return $rootLine;
    }

    /**
     * @return int
     */
    protected function getSrcPid(): int
    {
        return (int)($_GET['srcPID'] ?? null);
    }
}
