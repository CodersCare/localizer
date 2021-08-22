<?php

namespace Localizationteam\Localizer\Backend;

use Localizationteam\Localizer\Constants;
use PDO;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Cart itemsproc func
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class Cart
{
    protected static $data = [];

    /**
     * @param array $params
     * @param mixed $obj
     */
    public function filterList(&$params, $obj)
    {
        if ($obj instanceof TcaSelectItems) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
                Constants::TABLE_LOCALIZER_LANGUAGE_MM
            );
            $queryBuilder->getRestrictions()
                ->removeAll();
            $resource = $queryBuilder
                ->select('uid_foreign AS uid')
                ->from(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            'uid_local',
                            (int)$params['row']['uid']
                        ),
                        $queryBuilder->expr()->eq(
                            'tablenames',
                            $queryBuilder->createNamedParameter(Constants::TABLE_STATIC_LANGUAGES, PDO::PARAM_STR)
                        ),
                        $queryBuilder->expr()->eq(
                            'source',
                            $queryBuilder->createNamedParameter(Constants::TABLE_LOCALIZER_SETTINGS, PDO::PARAM_STR)
                        ),
                        $queryBuilder->expr()->eq(
                            'ident',
                            $queryBuilder->createNamedParameter('target', PDO::PARAM_STR)
                        )
                    )
                )
                ->execute();

            if ($resource->rowCount() > 0) {
                $keys = [];
                while ($row = $resource->fetchAssociative()) {
                    $keys[$row['uid']] = $row['uid'];
                }
                foreach ($params['items'] as $key => $item) {
                    if ($item[1] > 0) {
                        if (isset($keys[$item[1]]) === false) {
                            unset($params['items'][$key]);
                        }
                    }
                }
            } else {
                $params['items'] = [$params['items'][0]];
            }
        }
    }
}
