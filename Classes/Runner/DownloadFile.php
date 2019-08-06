<?php

namespace Localizationteam\Localizer\Runner;

use Exception;
use Localizationteam\Localizer\Api\ApiCalls;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use ZipArchive;

/**
 * Download translated files from Localizer
 *
 * @author      Peter Russ<peter.russ@4many.net>
 * @package     TYPO3
 * @date        20150920-1440
 * @subpackage  localizer
 *
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
        switch ($configuration['type']) {
            default :
                if (isset($configuration['processFiles'])) {
                    $this->processFiles = $configuration['processFiles'];
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
                }
        }
    }

    public function run()
    {
        $response = [];
        foreach ($this->processFiles as $files) {
            try {
                switch ($this->api->type) {
                    default:
                        $zip = new ZipArchive;
                        $file = str_replace('\\', '', $files['hotfolder']);
                        if ($zip->open($file) === true) {
                            $zip->extractTo(dirname($files['local']));
                            $zip->close();
                            //unlink($file);
                        } else {
                            throw new Exception('File could not successfully be unzipped');
                        }
                }
                $response[] = [
                    'http_status_code' => '200',
                ];
            } catch (Exception $e) {
                $response[] = $this->api->getLastError();
            }
        }
        $this->response = json_encode($response);
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
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
        $content{$start} = $iso2{0};
        $content{$start + 1} = $iso2{1};
    }
}