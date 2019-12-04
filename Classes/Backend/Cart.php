<?php

namespace Localizationteam\Localizer\Backend;

use Localizationteam\Localizer\DatabaseConnection;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Core\Utility\DebugUtility;

/**
 * Cart itemsproc func
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 *
 */
class Cart
{
    use DatabaseConnection;
    static protected $data = [];

    /**
     * @param array $params
     * @param mixed $obj
     */
    public function filterList(&$params, $obj)
    {
        if (isset($params['config']['filterList'])) {
            if ($obj instanceof TcaSelectItems) {
                $filter = $params['config']['filterList'];
                $where = $filter['field'] . ' = ' .
                    (int)$params['row']['uid'] .
                    $filter['where'];
                $field = (int)$filter['uid'] . ' AS uid';
                $storeLastBuiltQuery = false;
                if (isset($filter['debug']) && $filter['debug']) {
                    $storeLastBuiltQuery = $this->getDatabaseConnection()->store_lastBuiltQuery;
                    $this->getDatabaseConnection()->store_lastBuiltQuery = 1;
                }
                $res = $this->getDatabaseConnection()->exec_SELECTquery($field, $filter['table'], $where);
                if (isset($filter['debug']) && $filter['debug']) {
                    DebugUtility::debug($this->getDatabaseConnection()->debug_lastBuiltQuery,
                        __METHOD__ . ':' . __LINE__);
                    $this->getDatabaseConnection()->store_lastBuiltQuery = $storeLastBuiltQuery;
                }
                if ($res) {
                    $keys = [];
                    while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
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
}