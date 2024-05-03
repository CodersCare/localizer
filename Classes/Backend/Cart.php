<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Backend;

use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Data;
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
    use Data;

    /**
     * @param array $params
     * @param mixed $obj
     */
    public function filterList(array &$params, $obj): void
    {
        if (!$obj instanceof TcaSelectItems) {
            return;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_LANGUAGE_MM);
        $queryBuilder->getRestrictions()
            ->removeAll();
        $result = $queryBuilder
            ->select('uid_foreign AS uid')
            ->from(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
            ->where(
                $queryBuilder
                    ->expr()
                    ->and(
                        $queryBuilder->expr()->eq('uid_local', (int)$params['row']['uid']),
                        $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter(Constants::TABLE_STATIC_LANGUAGES)),
                        $queryBuilder->expr()->eq('source', $queryBuilder->createNamedParameter(Constants::TABLE_LOCALIZER_SETTINGS)),
                        $queryBuilder->expr()->eq('ident', $queryBuilder->createNamedParameter('target'))
                    )
            )->executeQuery();

        if ($result->rowCount() > 0) {
            $keys = [];
            while ($row = $this->fetchAssociative($result)) {
                $keys[$row['uid']] = $row['uid'];
            }

            foreach ($params['items'] as $key => $item) {
                if (($item[1] > 0) && isset($keys[$item[1]]) === false) {
                    unset($params['items'][$key]);
                }
            }
        } else {
            $params['items'] = [$params['items'][0]];
        }
    }
}
