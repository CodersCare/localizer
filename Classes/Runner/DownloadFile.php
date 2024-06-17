<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Runner;

use Exception;
use Localizationteam\Localizer\Api\ApiCalls;
use Localizationteam\Localizer\Api\ApiCallsInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use ZipArchive;

/**
 * Download translated files from Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class DownloadFile
{
    protected ApiCallsInterface $api;

    protected array $processFiles;

    protected array $response = [];

    protected string $path = '';

    public function init(array $configuration): void
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

    public function run(array $configuration): void
    {
        $response = [];
        switch ($configuration['type']) {
            case '0':
                foreach ($this->processFiles as $files) {
                    try {
                        $zip = new ZipArchive();
                        $file = str_replace('\\', '', $files['hotfolder']);
                        if ($configuration['plainxmlexports']) {
                            if (!copy($file, $files['local'])) {
                                throw new Exception('File could not successfully be copied');
                            }
                        } elseif ($zip->open($file) === true) {
                            $zip->extractTo(dirname($files['local']));
                            $zip->close();
                        } else {
                            throw new Exception('File could not successfully be unzipped');
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
        $this->response = $response;
    }

    protected function adjustContent(string &$content, string $iso2): void
    {
        $content = preg_replace(
            '#(<t3_targetLang(?: translate="no")?>)[^<]*(</t3_targetLang>)#',
            '$1' . $iso2 . '$2',
            $content,
        );
    }

    public function getResponse(): array
    {
        return $this->response;
    }
}
