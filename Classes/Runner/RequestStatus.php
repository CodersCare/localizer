<?php

namespace Localizationteam\Localizer\Runner;

use Exception;
use Localizationteam\Localizer\Api\ApiCalls;
use Localizationteam\Localizer\Constants;

/**
 * Requests translation status from Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>
 * @package     TYPO3
 * @date        20150910-1438
 * @subpackage  localizer
 *
 */
class RequestStatus
{
    /**
     * @var ApiCalls
     */
    protected $api;

    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var array
     */
    protected $response = [];

    /**
     *
     */
    public function __construct()
    {
    }

    /**
     * @param array $configuration
     */
    public function init(array $configuration)
    {
        switch ($configuration['type']) {
            default:
                if (isset($configuration['inFolder'])) {
                    if (isset($configuration['file'])) {
                        $this->path = PATH_site . trim($configuration['inFolder'], '\/') . '/' . str_replace('.xml', '',
                                $configuration['file']) . '.zip';
                    }
                }
        }
    }

    /**
     *
     */
    public function run()
    {
        try {
            if (file_exists($this->path)) {
                $this->response['http_status_code'] = 200;
                $this->response['files'] = [
                    [
                        'status' => Constants::API_TRANSLATION_STATUS_TRANSLATED,
                        'file'   => $this->path,
                    ],
                ];
            } else {
                $this->response['http_status_code'] = 200;
                $this->response['files'] = [
                    [
                        'status' => Constants::API_TRANSLATION_STATUS_IN_PROGRESS,
                        'file'   => $this->path,
                    ],
                ];
            }
        } catch (Exception $e) {
            $this->response = $e->getMessage();
        }
    }

    /**
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }
}