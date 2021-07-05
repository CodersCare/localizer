<?php

namespace Localizationteam\Localizer;

use Exception;
use Localizationteam\Localizer\Api\ApiCalls;
use Localizationteam\Localizer\Messaging\FlashMessage;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Data
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 *
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
            'error' => [],
        ];
        $this->apiPool = [];
        $this->data = [];
        $this->canPersist = true;
    }

    protected function load()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_EXPORTDATA_MM
        );
        $this->data = $queryBuilder
            ->select('*')
            ->from(Constants::TABLE_EXPORTDATA_MM)
            ->where(
                $queryBuilder->expr()->eq(
                    'processid',
                    $queryBuilder->createNamedParameter($this->getProcessId(), PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAllAssociative();
    }

    protected function loadCart()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_LOCALIZER_CART
        );
        $this->data = $queryBuilder
            ->select('*')
            ->from(Constants::TABLE_LOCALIZER_CART)
            ->where(
                $queryBuilder->expr()->eq(
                    'processid',
                    $queryBuilder->createNamedParameter($this->getProcessId(), PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAllAssociative();
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
            'status' => (int)$status,
            'previous_status' => (int)$previousStatus,
            'last_error' => (string)$lastError,
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
            'status' => (int)$status,
            'last_error' => null,
            'action' => (int)$action,
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_LOCALIZER_SETTINGS
        );
        $queryBuilder->getRestrictions();
        $row = $queryBuilder
            ->select(
                'uid',
                'type',
                'url',
                'workflow',
                'projectkey',
                'username',
                'password',
                'project_settings',
                'out_folder',
                'in_folder',
                'source_locale',
                'target_locale',
                'plainxmlexports'
            )
            ->from(Constants::TABLE_LOCALIZER_SETTINGS)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    (int)$uid
                )
            )
            ->execute()
            ->fetchAssociative();
        if ($row['type'] === '0' || ExtensionManagementUtility::isLoaded($row['type'])) {
            if ($row['type'] === '0') {
                $apiClass = ApiCalls::class;
            } else {
                $apiClass = 'Localizationteam\\' . GeneralUtility::underscoredToUpperCamelCase(
                    $row['type']
                ) . '\\Api\\ApiCalls';
            }
            $api = GeneralUtility::makeInstance(
                $apiClass,
                0,
                $row['url'],
                $row['workflow'],
                $row['projectkey'],
                $row['username'],
                $row['password'],
                $row['out_folder'],
                $row['in_folder'],
                (bool)$row['plainxmlexports']
            );
            if ($row['type'] !== '0' || $api->checkAndCreateFolders() === true) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
                    Constants::TABLE_LOCALIZER_LANGUAGE_MM
                );
                $queryBuilder->getRestrictions();
                $sourceLocale = $queryBuilder
                    ->select('*')
                    ->from(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
                    ->leftJoin(
                        Constants::TABLE_LOCALIZER_LANGUAGE_MM,
                        Constants::TABLE_STATIC_LANGUAGES,
                        Constants::TABLE_STATIC_LANGUAGES,
                        $queryBuilder->expr()->eq(
                            Constants::TABLE_STATIC_LANGUAGES . '.uid',
                            $queryBuilder->quoteIdentifier(Constants::TABLE_LOCALIZER_LANGUAGE_MM . '.uid_foreign')
                        )
                    )
                    ->where(
                        $queryBuilder->expr()->andX(
                            $queryBuilder->expr()->eq(
                                'uid_local',
                                (int)$row['uid']
                            ),
                            $queryBuilder->expr()->eq(
                                'ident',
                                $queryBuilder->createNamedParameter('source', PDO::PARAM_STR)
                            ),
                            $queryBuilder->expr()->eq(
                                'tablenames',
                                $queryBuilder->createNamedParameter('static_languages', PDO::PARAM_STR)
                            ),
                            $queryBuilder->expr()->eq(
                                'source',
                                $queryBuilder->createNamedParameter('tx_localizer_settings', PDO::PARAM_STR)
                            )
                        )
                    )
                    ->execute()
                    ->fetchAssociative();
                $this->apiPool[$uid] = [
                    'api' => $api,
                    'settings' => [
                        'type' => $row['type'],
                        'url' => $row['url'],
                        'outFolder' => $row['out_folder'],
                        'inFolder' => $row['in_folder'],
                        'projectKey' => $row['projectkey'],
                        'token' => $api->getToken(),
                        'username' => $row['username'],
                        'password' => $row['password'],
                        'workflow' => $row['workflow'],
                        'source' => $sourceLocale['lg_collate_locale'],
                        'plainxmlexports' => (bool)$row['plainxmlexports'],
                    ],
                ];
            }
        } else {
            $this->apiPool[$uid] = false;
            new FlashMessage(
                'Localizer settings [' . $uid . '] either disabled or deleted or API plugin not available anymore',
                3
            );
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
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
                    Constants::TABLE_LOCALIZER_CART
                );
                $queryBuilder
                    ->update(Constants::TABLE_LOCALIZER_CART)
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            (int)$uid
                        )
                    );
                foreach ($fields as $key => $value) {
                    $queryBuilder->set($key, $value);
                }
                $queryBuilder->execute();
            }
            foreach ($this->result['success'] as $uid => $fields) {
                $fields['tstamp'] = (int)$time;
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
                    Constants::TABLE_EXPORTDATA_MM
                );
                $queryBuilder
                    ->update(Constants::TABLE_EXPORTDATA_MM)
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            (int)$uid
                        )
                    );
                foreach ($fields as $key => $value) {
                    $queryBuilder->set($key, $value);
                }
                $queryBuilder->execute();
            }
        }
    }
}
