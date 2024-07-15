<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Traits;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Result;
use Localizationteam\Localizer\Api\ApiCalls;
use Localizationteam\Localizer\Api\ApiCallsInterface;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Messaging\FlashMessage;
use Localizationteam\Localizer\Model\Repository\SettingsRepository;
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
    use Language;
    protected array $apiPool;

    protected array $data;

    protected ?array $result = null;

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

    /**
     * @throws DBALException
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected function load(): void
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(
            Constants::TABLE_EXPORTDATA_MM
        );

        $this->data = $queryBuilder
            ->select('*')
            ->from(Constants::TABLE_EXPORTDATA_MM)
            ->where(
                $queryBuilder->expr()->eq(
                    'processid',
                    $queryBuilder->createNamedParameter($this->getProcessId())
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    protected function loadCart(): void
    {
        $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(
            Constants::TABLE_LOCALIZER_CART
        );
        $this->data = $queryBuilder
            ->select('*')
            ->from(Constants::TABLE_LOCALIZER_CART)
            ->where(
                $queryBuilder->expr()->eq(
                    'processid',
                    $queryBuilder->createNamedParameter($this->getProcessId())
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
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
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected function getLocalizerSettings(int $uid): array
    {
        /** @var SettingsRepository $localizerSettingsRepository */
        $localizerSettingsRepository = GeneralUtility::makeInstance(SettingsRepository::class);

        $fields = [
            'uid',
            'pid',
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
            'source_language',
            'target_languages',
            'plainxmlexports',
        ];

        $row = $localizerSettingsRepository->findByUid($uid, $fields);

        if (isset($row['type']) && ($row['type'] === '0' || ExtensionManagementUtility::isLoaded($row['type'] ?? ''))) {
            if ($row['type'] === '0') {
                $apiClass = ApiCalls::class;
            } else {
                $apiClass = 'Localizationteam\\' . GeneralUtility::underscoredToUpperCamelCase(
                    $row['type']
                ) . '\\Api\\ApiCalls';
            }
            $api = GeneralUtility::makeInstance(
                $apiClass,
                '0',
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
                        'source' => $this->getIso2ForLocale($row),
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

    /**
     * @throws DBALException
     */
    protected function persistsResult(int $time): void
    {
        if ($this->canPersist === true) {
            foreach ($this->result['error'] as $uid => $fields) {
                $fields['tstamp'] = $time;
                $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(
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
                $queryBuilder->executeStatement();
            }
            foreach ($this->result['success'] as $uid => $fields) {
                $fields['tstamp'] = $time;
                $queryBuilder = self::getConnectionPool()->getQueryBuilderForTable(
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

                $queryBuilder->executeStatement();
            }
        }
    }
}
