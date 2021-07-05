<?php

namespace Localizationteam\Localizer\Runner;

use Exception;
use Localizationteam\Localizer\Api\ApiCalls;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use ZipArchive;

/**
 * Download translated files from Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class DownloadFile
{
    /**
     * @var ApiCalls
     */
    protected $api;

    /**
     * @var array
     */
    protected $processFiles;

    /**
     * @var string
     */
    protected $response = '';

    /**
     * @var string
     */
    protected $path = '';

    /**
     * @param array $configuration
     */
    public function init(array $configuration)
    {
        if (isset($configuration['processFiles'])) {
            $this->processFiles = $configuration['processFiles'];

            switch ($configuration['type']) {
                case '0':
                    if (isset($configuration['inFolder'])) {
                        if (isset($configuration['projectKey'])) {
                            $this->api = GeneralUtility::makeInstance(
                                ApiCalls::class,
                                $configuration['type'],
                                '',
                                $configuration['workflow'],
                                $configuration['projectKey'],
                                '',
                                '',
                                '',
                                $configuration['inFolder']
                            );
                            if (isset($configuration['file'])) {
                                $this->path = str_replace('.xml', '', $configuration['file']) . '.xml';
                            }
                        }
                    }
                    break;
                default:
                    if (isset($configuration['token'], $configuration['url'], $configuration['projectKey'])) {
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
                            ''
                        );
                        $this->api->setToken($configuration['token']);
                        if (isset($configuration['file'])) {
                            $this->path = $configuration['file'] . '.xml';
                        }
                    }
            }
        }
    }

    public function run($configuration)
    {
        $response = [];
        switch ($configuration['type']) {
            case '0':
                foreach ($this->processFiles as $files) {
                    try {
                        switch ($this->api->type) {
                            default:
                                $zip = new ZipArchive();
                                $file = str_replace('\\', '', $files['hotfolder']);
                                if ($configuration['plainxmlexports']) {
                                    if (!copy($file, $files['local'])) {
                                        throw new Exception('File could not successfully be copied');
                                    }
                                } else {
                                    if ($zip->open($file) === true) {
                                        $zip->extractTo(dirname($files['local']));
                                        $zip->close();
                                    //unlink($file);
                                    } else {
                                        throw new Exception('File could not successfully be unzipped');
                                    }
                                }
                        }
                        $response[] = [
                            'http_status_code' => '200',
                        ];
                    } catch (Exception $e) {
                        $response[] = $this->api->getLastError();
                    }
                }
                break;
            default:
                foreach ($this->processFiles as $file) {
                    try {
                        $fileContent = $this->api->getFile($file);
                        $this->adjustContent($fileContent, $file['locale']);
                        file_put_contents($file['local'], $fileContent);
                        $response[] = [
                            'http_status_code' => '200',
                        ];
                    } catch (Exception $e) {
                        $response[] = $this->api->getLastError();
                    }
                }
        }
        $this->response = json_encode($response);
    }

    /**
     * @param string $content
     * @param string $iso2
     */
    protected function adjustContent(&$content, $iso2)
    {
        $search = '<t3_targetLang>';
        $position = strpos($content, $search);
        $start = $position + strlen($search);
        $content = substr_replace($content, $iso2, $start, 0);
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }
}
