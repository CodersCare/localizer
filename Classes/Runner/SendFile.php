<?php

namespace Localizationteam\Localizer\Runner;

use Exception;
use Localizationteam\Localizer\Api\ApiCalls;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Send file to Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 *
 */
class SendFile
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
     * @var int
     */
    protected $type;

    /**
     * @var string
     */
    protected $source = '';

    /**
     * @var string
     */
    protected $localFile = '';

    /**
     * @var string
     */
    protected $response = '';

    /**
     * @var array
     */
    protected $targetLocales = [];

    protected $deadline = 0;

    protected $metaData = [];

    protected $sendAttachment = false;

    /**
     * @param array $configuration
     * @throws Exception
     */
    public function init(array $configuration)
    {
        if (isset($configuration['type'])) {
            if (isset($configuration['localFile'])) {
                if (file_exists($configuration['localFile'])) {
                    $this->localFile = $configuration['localFile'];
                    if (isset($configuration['source'])) {
                        $this->source = $configuration['source'];
                        switch ((string)$configuration['type']) {
                            case '0' :
                                if (isset($configuration['outFolder'])) {
                                    $this->api = GeneralUtility::makeInstance(
                                        ApiCalls::class,
                                        $configuration['type'],
                                        '',
                                        $configuration['workflow'],
                                        $configuration['projectKey'],
                                        '',
                                        '',
                                        $configuration['outFolder'],
                                        '',
                                        (bool)$configuration['plainxmlexports']
                                    );
                                    if (isset($configuration['file'])) {
                                        $this->path = str_replace('.xml', '', $configuration['file']) . '.xml';
                                    }
                                    if (isset($configuration['deadline'])) {
                                        $this->deadline = (int)$configuration['deadline'];
                                    }
                                    if (isset($configuration['targetLocales'])) {
                                        $this->targetLocales = $configuration['targetLocales'];
                                    }
                                    if (isset($configuration['metadata'])) {
                                        $this->metaData = $configuration['metadata'];
                                    }
                                } else {
                                    throw new Exception(
                                        'No out folder given. Please set one in the localizer settings'
                                    );
                                }
                                break;
                            default :
                                if (ExtensionManagementUtility::isLoaded($configuration['type'])) {
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
                                        if (isset($configuration['file'])) {
                                            $this->path = str_replace('.xml', '', $configuration['file']) . '.xml';
                                        }
                                        if (isset($configuration['deadline'])) {
                                            $this->deadline = (int)$configuration['deadline'];
                                        }
                                        if (isset($configuration['targetLocales'])) {
                                            $this->targetLocales = $configuration['targetLocales'];
                                        }
                                        if (isset($configuration['metadata'])) {
                                            $this->metaData = $configuration['metadata'];
                                        }
                                    } else {
                                        throw new Exception(
                                            'No project key given. Please set one in the localizer settings'
                                        );
                                    }
                                } else {
                                    throw new Exception(
                                        'Missing API plugin ' . $configuration['type'] . '. Please install the necessary API plugin extension'
                                    );
                                }
                        }
                    } else {
                        throw new Exception('No source given. Please set one in the localizer settings');
                    }
                }
            } else {
                throw new Exception('No local file given. Please set one in the localizer settings');
            }
        } else {
            throw new Exception('No type given. Please set one in the localizer settings');
        }
    }

    /**
     *
     * @throws Exception
     */
    public function run()
    {
        $this->prepareInstructions();
        $this->sendFile();
        $this->setResponse();
    }

    /**
     * @throws Exception
     */
    protected function prepareInstructions()
    {
        $this->api->resetInstructions();
        if ($this->deadline > 0) {
            $this->api->setDeadline($this->deadline);
            $this->sendAttachment = true;
        }
        if (count($this->targetLocales) > 0) {
            $this->api->setLocales($this->targetLocales);
            $this->sendAttachment = true;
        }
        if (count($this->metaData) > 0) {
            $this->api->setMetaData($this->metaData);
            $this->sendAttachment = true;
        }
    }

    /**
     * @throws Exception
     */
    protected function sendFile()
    {
        $this->api->sendFile(
            file_get_contents($this->localFile),
            $this->path,
            $this->source,
            $this->sendAttachment
        );
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response ?: '';
    }

    protected function setResponse()
    {
        $this->response = $this->api->getLastError();
    }
}