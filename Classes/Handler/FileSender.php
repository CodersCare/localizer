<?php

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Data;
use Localizationteam\Localizer\Language;
use Localizationteam\Localizer\Model\Repository\LanguageRepository;
use Localizationteam\Localizer\Runner\SendFile;
use PDO;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * FileSender takes care to send file(s) to Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class FileSender extends AbstractHandler
{
    use Data;
    use Language;

    /**
     * @var string
     */
    protected $uploadPath = '';

    /**
     * @param $id
     * @throws Exception
     */
    public function init($id = 1)
    {
        $this->initProcessId();
        if ($this->acquire()) {
            $this->initRun();
        }
        if ($this->canRun()) {
            $this->initData();
            $this->load();
        }
    }

    protected function acquire(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_EXPORTDATA_MM
        );
        $affectedRows = $queryBuilder
            ->update(Constants::TABLE_EXPORTDATA_MM)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        'status',
                        Constants::HANDLER_FILESENDER_START
                    ),
                    $queryBuilder->expr()->eq(
                        'action',
                        Constants::ACTION_SEND_FILE
                    ),
                    $queryBuilder->expr()->isNull(
                        'last_error'
                    ),
                    $queryBuilder->expr()->eq(
                        'processid',
                        $queryBuilder->createNamedParameter('', PDO::PARAM_STR)
                    )
                )
            )
            ->set('tstamp', time())
            ->set('processid', $this->processId)
            ->setMaxResults(Constants::HANDLER_FILESENDER_MAX_FILES)
            ->execute();

        return $affectedRows > 0;
    }

    /**
     * @throws \TYPO3\CMS\Core\Exception
     */
    public function run()
    {
        if ($this->canRun() === true) {
            foreach ($this->data as $row) {
                $file = $this->getFileAndPath($row['filename']);
                if ($file === false) {
                    $this->addErrorResult(
                        $row['uid'],
                        Constants::STATUS_CART_ERROR,
                        $row['status'],
                        'File ' . $row['filename'] . ' not found'
                    );
                } else {
                    $localizerSettings = $this->getLocalizerSettings($row['uid_local']);
                    if ($localizerSettings === false) {
                        $this->addErrorResult(
                            $row['uid'],
                            Constants::STATUS_CART_ERROR,
                            $row['status'],
                            'LOCALIZER settings (' . $row['uid_local'] . ') not found'
                        );
                    } else {
                        $additionalConfiguration = [
                            'uid' => $row['uid'],
                            'localFile' => $file,
                            'file' => $row['filename'],
                        ];
                        $deadline = $this->addDeadline($row);
                        if (!empty($deadline)) {
                            $additionalConfiguration['deadline'] = $deadline;
                        }
                        $metadata = $this->addMetaData($row);
                        if (!empty($metadata)) {
                            $additionalConfiguration['metadata'] = $metadata;
                        }
                        $translateAll = $this->translateAll($row);
                        if ($translateAll === false) {
                            /** @var LanguageRepository $languageRepository */
                            $languageRepository = GeneralUtility::makeInstance(LanguageRepository::class);
                            $targetLocalesUids = $languageRepository->getAllTargetLanguageUids(
                                $row['uid'],
                                Constants::TABLE_EXPORTDATA_MM
                            );
                            $additionalConfiguration['targetLocales'] =
                                $languageRepository->getStaticLanguagesCollateLocale($targetLocalesUids, true);
                        }
                        $configuration = array_merge(
                            (array)$localizerSettings,
                            $additionalConfiguration
                        );
                        if ((int)$row['action'] === Constants::ACTION_SEND_FILE) {
                            /** @var SendFile $runner */
                            $runner = GeneralUtility::makeInstance(SendFile::class);
                            $runner->init($configuration);
                            $runner->run();
                            $response = $runner->getResponse();
                            if (empty($response)) {
                                $this->addSuccessResult(
                                    $row['uid'],
                                    Constants::STATUS_CART_FILE_SENT,
                                    Constants::ACTION_REQUEST_STATUS
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $fileName
     * @return bool|string
     */
    protected function getFileAndPath($fileName)
    {
        $file = $this->getUploadPath() . $fileName;
        return file_exists($file) ? $file : false;
    }

    /**
     * @return string
     */
    protected function getUploadPath(): string
    {
        if ($this->uploadPath === '') {
            $this->uploadPath = Environment::getPublicPath() . '/uploads/tx_l10nmgr/jobs/out/';
        }
        return $this->uploadPath;
    }

    /**
     * @param array $row
     * @return int
     */
    protected function addDeadline(array &$row): int
    {
        $deadline = 0;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(
            Constants::TABLE_EXPORTDATA_MM
        );
        $result = $queryBuilder
            ->selectLiteral(
                'COALESCE (
                NULLIF(' . Constants::TABLE_EXPORTDATA_MM . '.deadline, 0), ' .
                Constants::TABLE_LOCALIZER_CART . '.deadline
            ) deadline'
            )
            ->from(Constants::TABLE_EXPORTDATA_MM)
            ->leftJoin(
                Constants::TABLE_EXPORTDATA_MM,
                Constants::TABLE_LOCALIZER_CART,
                Constants::TABLE_LOCALIZER_CART,
                $queryBuilder->expr()->eq(
                    Constants::TABLE_LOCALIZER_CART . '.uid_foreign',
                    $queryBuilder->quoteIdentifier(Constants::TABLE_EXPORTDATA_MM . '.uid_foreign')
                )
            )->where(
                $queryBuilder->expr()->eq(
                    Constants::TABLE_EXPORTDATA_MM . '.uid',
                    (int)$row['uid']
                )
            )
            ->execute();
        $carts = $this->fetchAssociative($result);

        if (!empty($carts['deadline'])) {
            $deadline = (int)$carts['deadline'];
        }
        return $deadline;
    }

    /**
     * @param array $row
     * @return array
     */
    protected function addMetaData(array &$row): array
    {
        $metaData = [];
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['localizer']['addMetaData'];
        if (is_array($hooks)) {
            foreach ($hooks as $hookObj) {
                $metaData = GeneralUtility::callUserFunction($hookObj, $row, $this);
            }
        }
        return $metaData;
    }

    /**
     * @param int $time
     */
    public function finish(int $time)
    {
        $this->dataFinish($time);
    }
}
