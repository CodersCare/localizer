<?php

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Data;
use Localizationteam\Localizer\Runner\RequestStatus;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * StatusRequester takes care to request the translation status for file(s) from Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 *
 */
class StatusRequester extends AbstractHandler
{
    use Data;

    /**
     * @param $id
     * @throws Exception
     */
    public function init($id = 1)
    {
        $where = 'deleted = 0 AND hidden = 0 AND  status >= ' . Constants::HANDLER_STATUSREQUESTER_START .
            ' AND status < ' . Constants::HANDLER_STATUSREQUESTER_FINISH .
            ' AND action = ' . Constants::ACTION_REQUEST_STATUS .
            ' AND last_error = "" AND processid = ""';
        $this->setAcquireWhere($where);
        parent::init($id);
        if ($this->canRun()) {
            $this->initData();
            $this->load();
        }
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        if ($this->canRun() === true) {
            foreach ($this->data as $row) {
                $localizerSettings = $this->getLocalizerSettings($row['uid_local']);
                if ($localizerSettings === false) {
                    $this->addErrorResult(
                        $row['uid'],
                        Constants::STATUS_CART_ERROR,
                        $row['status'],
                        'LOCALIZER settings (' . $row['uid_local'] . ') not found'
                    );
                } else {
                    $configuration = array_merge(
                        (array)$localizerSettings,
                        [
                            'file' => $row['filename'],
                        ]
                    );
                    /** @var RequestStatus $runner */
                    $runner = GeneralUtility::makeInstance(RequestStatus::class);
                    $runner->init($configuration);
                    $runner->run();
                    $response = $runner->getResponse();
                    if (isset($response['http_status_code'])) {
                        if ($response['http_status_code'] == 200) {
                            $this->processResponse($row['uid'], $response);
                        } else {
                            DebugUtility::debug($response, 'ERROR');
                        }
                    } else {
                        DebugUtility::debug($response, __LINE__);
                        //todo: more error handling
                    }
                }
            }
        }
    }

    /**
     * @param int $uid
     * @param array|string $response
     */
    protected function processResponse($uid, array $response)
    {
        $translationStatus = 0;
        if (isset($response['files'])) {
            foreach ($response['files'] as $fileStatus) {
                if ((int)$fileStatus['status'] > $translationStatus) {
                    $translationStatus = (int)$fileStatus['status'];
                }
            }
            $action = Constants::ACTION_REQUEST_STATUS;
            $status = Constants::STATUS_CART_TRANSLATION_IN_PROGRESS;
            $originalResponse = '';
            switch ($translationStatus) {
                case Constants::API_TRANSLATION_STATUS_IN_PROGRESS:
                case Constants::API_TRANSLATION_STATUS_WAITING:
                    $status = Constants::STATUS_CART_TRANSLATION_IN_PROGRESS;
                    break;
                case Constants::API_TRANSLATION_STATUS_TRANSLATED:
                    $status = Constants::STATUS_CART_TRANSLATION_FINISHED;
                    $action = Constants::ACTION_DOWNLOAD_FILE;
                    $originalResponse = $response;
                    break;
            }
            $this->addSuccessResult($uid, $status, $action, $originalResponse);
        }
    }

    /**
     * @param int $time
     * @return void
     */
    function finish($time)
    {
        $this->dataFinish($time);
    }
}