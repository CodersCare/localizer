<?php

namespace Localizationteam\Localizer;

use Exception;
use Localizationteam\Localizer\Api\ApiCalls;
use Localizationteam\Localizer\Messaging\FlashMessage;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Data $COMMENT$
 *
 * @author      Peter Russ<peter.russ@4many.net>
 * @package     TYPO3
 * @date        20150920-1014
 * @subpackage  localizer
 *
 * @method DatabaseConnection getDatabaseConnection() must be defined in implementing class
 * @method string getProcessId() must be defined in implementing class
 */
trait Data
{
    /**
     * @var array
     */
    protected $apiPool;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $result;

    /**
     * @var bool
     */
    private $canPersist = false;

    protected function initData()
    {
        $this->result = [
            'success' => [],
            'error'   => [],
        ];
        $this->apiPool = [];
        $this->data = [];
        $this->canPersist = true;
    }

    protected function load()
    {
        $this->data = $this->getDatabaseConnection()
            ->exec_SELECTgetRows(
                '*',
                Constants::TABLE_EXPORTDATA_MM,
                'processid = "' . $this->getProcessId() . '"'
            );
    }

    protected function loadCart()
    {
        $this->data = $this->getDatabaseConnection()
            ->exec_SELECTgetRows(
                '*',
                Constants::TABLE_LOCALIZER_CART,
                'processid = "' . $this->getProcessId() . '"'
            );
    }

    /**
     * @param int $uid
     * @param int $status
     * @param $previousStatus
     * @param string $lastError
     * @param int $action
     */
    protected function addErrorResult($uid, $status, $previousStatus, $lastError, $action = 0)
    {
        $this->result['error'][(int)$uid] = [
            'status'          => (int)$status,
            'previous_status' => (int)$previousStatus,
            'last_error'      => (string)$lastError,
        ];
        if ($action > 0) {
            $this->result['error'][(int)$uid]['action'] = $action;
        }
    }

    /**
     * @param int $uid
     * @param int $status
     * @param int $action
     * @param mixed $response
     */
    protected function addSuccessResult($uid, $status, $action = 0, $response = '')
    {
        if (is_array($response)) {
            $response = json_encode($response);
        }
        $this->result['success'][(int)$uid] = [
            'status'     => (int)$status,
            'last_error' => '',
            'action'     => (int)$action,
        ];
        if ($response !== '') {
            $this->result['success'][(int)$uid]['response'] = (string)$response;
        }
    }

    /**
     * @param int $uid
     * @return bool|array
     * @throws Exception
     */
    protected function getLocalizerSettings($uid)
    {
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid,type,url,workflow,projectkey,username,password,project_settings,out_folder,in_folder,source_locale,target_locale',
            Constants::TABLE_LOCALIZER_SETTINGS,
            'deleted = 0 AND hidden = 0 AND uid = ' . (int)$uid
        );
        if ((int)$row['type'] === 0 && !empty($row['out_folder']) && !empty($row['in_folder'])) {
            /** @var ApiCalls $api */
            $api = GeneralUtility::makeInstance(
                ApiCalls::class,
                0,
                '',
                $row['workflow'],
                $row['projectkey'],
                $row['username'],
                $row['password'],
                $row['out_folder'],
                $row['in_folder']
            );
            if ($api->checkAndCreateFolders() === true) {
                $sourceLocale = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                    '*',
                    Constants::TABLE_LOCALIZER_LANGUAGE_MM .
                    ' LEFT OUTER JOIN ' . Constants::TABLE_STATIC_LANGUAGES . ' ON ' . Constants::TABLE_STATIC_LANGUAGES . '.uid=' . Constants::TABLE_LOCALIZER_LANGUAGE_MM . '.uid_foreign',
                    "uid_local=" . (int)$row['uid'] .
                    " AND ident='source' AND tablenames='static_languages' AND source='tx_localizer_settings'"
                );
                $this->apiPool[$uid] = [
                    'api'      => $api,
                    'settings' => [
                        'type'       => $row['type'],
                        'outFolder'  => $row['out_folder'],
                        'inFolder'   => $row['in_folder'],
                        'projectKey' => $row['projectkey'],
                        'workflow'   => $row['workflow'],
                        'source'     => $sourceLocale['lg_collate_locale'],
                    ],
                ];
            }
        } else {
            $this->apiPool[$uid] = false;
            new FlashMessage('Localizer settings [' . $uid . '] either disabled or deleted', 3);
        }
        return $this->apiPool[$uid] === false ?
            false :
            $this->apiPool[$uid]['settings'];
    }

    /**
     * @param int $time
     */
    protected function dataFinish($time)
    {
        $this->persistsResult($time);
    }

    /**
     * @param int $time
     */
    protected function persistsResult($time)
    {
        if ($this->canPersist === true) {
            foreach ($this->result['error'] as $uid => $fields) {
                $fields['tstamp'] = (int)$time;
                $this->getDatabaseConnection()->exec_UPDATEquery(
                    Constants::TABLE_EXPORTDATA_MM,
                    'uid=' . $uid,
                    $fields
                );
            }
            foreach ($this->result['success'] as $uid => $fields) {
                $fields['tstamp'] = (int)$time;
                $this->getDatabaseConnection()->exec_UPDATEquery(
                    Constants::TABLE_EXPORTDATA_MM,
                    'uid=' . $uid,
                    $fields
                );
            }
        }
    }

}