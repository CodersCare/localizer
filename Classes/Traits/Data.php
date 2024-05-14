<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Traits;

use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Result;
use Localizationteam\Localizer\Api\ApiCalls;
use Localizationteam\Localizer\Api\ApiCallsInterface;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Messaging\FlashMessage;
use Localizationteam\Localizer\Model\Repository\SettingsRepository;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception;
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
    protected array $apiPool;

    protected array $data;

    protected array $result;

    private bool $canPersist = false;

    protected function initData(): void
    {
        $this->result = [
            'success' => [],
            'error' => [],
        ];
        $this->apiPool = [];
        $this->data = [];
        $this->canPersist = true;
    }

    protected function load(): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_EXPORTDATA_MM
        );
        $result = $queryBuilder
            ->select('*')
            ->from(Constants::TABLE_EXPORTDATA_MM)
            ->where(
                $queryBuilder->expr()->eq(
                    'processid',
                    $queryBuilder->createNamedParameter($this->getProcessId())
                )
            )
            ->executeQuery();
        $this->data = $this->fetchAllAssociative($result);
    }

    protected function loadCart(): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_LOCALIZER_CART
        );
        $result = $queryBuilder
            ->select('*')
            ->from(Constants::TABLE_LOCALIZER_CART)
            ->where(
                $queryBuilder->expr()->eq(
                    'processid',
                    $queryBuilder->createNamedParameter($this->getProcessId())
                )
            )
            ->executeQuery();
        $this->data = $this->fetchAllAssociative($result);
    }

    protected function addErrorResult(int $uid, int $status, int $previousStatus, string $lastError, int $action = 0): void
    {
        $this->result['error'][$uid] = [
            'status' => $status,
            'previous_status' => $previousStatus,
            'last_error' => $lastError,
        ];
        if ($action > 0) {
            $this->result['error'][$uid]['action'] = $action;
        }
    }

    protected function addSuccessResult(int $uid, int $status, int $action = 0, array $response = []): void
    {
        $this->result['success'][$uid] = [
            'status' => $status,
            'last_error' => null,
            'action' => $action,
        ];
        if (!empty($response)) {
            $this->result['success'][$uid]['response'] = json_encode($response);
        }
    }

    /**
     * @throws Exception
     */
    protected function getLocalizerSettings(int $uid): array
    {
        /** @var SettingsRepository $localizerSettingsRepository */
        $localizerSettingsRepository = GeneralUtility::makeInstance(SettingsRepository::class);

        $fields = [
            'uid',
            'type',
            'title',
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
            'plainxmlexports',
        ];

        $row = $localizerSettingsRepository->findByUid($uid, $fields);

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
            if ($api instanceof ApiCallsInterface && (!$api instanceof ApiCalls || $api->checkAndCreateFolders())) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable(Constants::TABLE_LOCALIZER_LANGUAGE_MM);
                $result = $queryBuilder
                    ->select('*')
                    ->from(Constants::TABLE_LOCALIZER_LANGUAGE_MM)
                    ->leftJoin(
                        Constants::TABLE_LOCALIZER_LANGUAGE_MM,
                        Constants::TABLE_STATIC_LANGUAGES,
                        Constants::TABLE_STATIC_LANGUAGES,
                        (string)$queryBuilder->expr()->eq(
                            Constants::TABLE_STATIC_LANGUAGES . '.uid',
                            $queryBuilder->quoteIdentifier(Constants::TABLE_LOCALIZER_LANGUAGE_MM . '.uid_foreign')
                        )
                    )
                    ->where(
                        $queryBuilder->expr()->and(
                            $queryBuilder->expr()->eq(
                                'uid_local',
                                (int)$row['uid']
                            ),
                            $queryBuilder->expr()->eq(
                                'ident',
                                $queryBuilder->createNamedParameter('source')
                            ),
                            $queryBuilder->expr()->eq(
                                'tablenames',
                                $queryBuilder->createNamedParameter('static_languages')
                            ),
                            $queryBuilder->expr()->eq(
                                'source',
                                $queryBuilder->createNamedParameter('tx_localizer_settings')
                            )
                        )
                    )
                    ->executeQuery();
                $sourceLocale = $this->fetchAssociative($result);
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
                        'source' => str_replace('_', '-', $sourceLocale['lg_collate_locale']),
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
            [] :
            $this->apiPool[$uid]['settings'];
    }

    protected function dataFinish(int $time): void
    {
        $this->persistsResult($time);
    }

    protected function persistsResult(int $time): void
    {
        if ($this->canPersist === true) {
            foreach ($this->result['error'] as $uid => $fields) {
                $fields['tstamp'] = $time;
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
                $fields['tstamp'] = $time;
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

    /**
     * @return mixed
     */
    public function fetchOne(ResultStatement $result)
    {
        if (method_exists($result, 'fetchOne')) {
            return $result->fetchOne();
        }
        return $result->fetchColumn();
    }

    /**
     * @return mixed
     */
    public function fetchAssociative(Result $result)
    {
        if (method_exists($result, 'fetchAssociative')) {
            return $result->fetchAssociative();
        }
        return $result->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @return mixed
     */
    public function fetchAllAssociative(Result $result)
    {
        if (method_exists($result, 'fetchAllAssociative')) {
            return $result->fetchAllAssociative();
        }
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }
}
