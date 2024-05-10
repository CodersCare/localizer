<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Runner;

use Exception;
use Localizationteam\Localizer\Api\ApiCallsInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Requests translation status from Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class ReportSuccess
{
    protected ApiCallsInterface $api;

    protected string $path = '';

    protected array $response = [];

    public function init(array $configuration): void
    {
        switch ($configuration['type']) {
            case '0':
                if (isset($configuration['inFolder'])) {
                    if (isset($configuration['file'])) {
                        $this->path = Environment::getPublicPath() . '/' . trim(
                            $configuration['inFolder'],
                            '\/'
                        ) . '/';
                        if ($configuration['plainxmlexports']) {
                            $this->path .= $configuration['file'];
                        } else {
                            $this->path .= str_replace(
                                '.xml',
                                '',
                                $configuration['file']
                            ) . '.zip';
                        }
                    }
                }
                break;
            default:
                if (isset($configuration['token'])) {
                    if (isset($configuration['url'])) {
                        if (isset($configuration['projectKey'])) {
                            $this->api = GeneralUtility::makeInstance(
                                'Localizationteam\\' . GeneralUtility::underscoredToUpperCamelCase(
                                    $configuration['type']
                                ) . '\\Api\\ApiCalls',
                                $configuration['type'],
                                $configuration['url'],
                                $configuration['workflow'],
                                $configuration['projectKey'],
                                $configuration['username'],
                                $configuration['password'],
                                $configuration['uid']
                            );
                            $this->api->setToken($configuration['token']);
                            if (isset($configuration['file'])) {
                                $this->path = $configuration['file'];
                            }
                        }
                    }
                }
        }
    }

    public function run(array $configuration): void
    {
        switch ($configuration['type']) {
            case '0':
                $this->response['http_status_code'] = 200;
                break;
            default:
                try {
                    $this->response = $this->api->reportSuccess(
                        (array)$this->path,
                        $configuration['target']
                    );
                    $this->response['http_status_code'] = 200;
                } catch (Exception $e) {
                    $this->response = [$e];
                }
        }
    }

    public function getResponse(): array
    {
        return $this->response;
    }
}
