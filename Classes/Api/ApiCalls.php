<?php

namespace Localizationteam\Localizer\Api;

use Exception;
use Localizationteam\Localizer\BackendUser;
use Localizationteam\Localizer\Constants;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use ZipArchive;

/**
 * ApiCalls Class used to make calls to the Localizer API
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 *
 */
class ApiCalls
{
    use BackendUser;
    /**
     * @var int
     */
    public $type;

    /**
     * @var string
     */
    protected $connectorName;

    /**
     * @var string
     */
    protected $connectorVersion;

    /**
     * @var string
     */
    protected $token = '';

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $projectKey;

    /**
     * @var string
     */
    protected $workflow;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $outFolder;

    /**
     * @var string
     */
    protected $inFolder;

    /**
     * @var bool
     */
    protected $plainXmlExports;

    /**
     * @var string
     */
    protected $deadline = '';

    /**
     * @var int
     */
    protected $deadlineOffset = 0;

    /**
     * @var array
     */
    protected $metaData = [];

    /**
     * @var array
     */
    protected $projectLanguages = null;

    /**
     * @var array
     */
    protected $locales = [];

    /**
     * @var string
     */
    protected $sourceLanguage = '';

    /**
     * @var null
     */
    protected $projectInformation = null;

    /**
     * @var null
     */
    protected $folderInformation = null;

    /**
     * @var string
     */
    protected $lastError = '';

    /**
     * @param int $type
     * @param string $url
     * @param string $workflow
     * @param string $projectKey
     * @param string $username
     * @param string $password
     * @param string $outFolder
     * @param string $inFolder
     * @param bool $plainXmlExports
     */
    public function __construct(
        $type,
        $url = '',
        $workflow = '',
        $projectKey = '',
        $username = '',
        $password = '',
        $outFolder = '',
        $inFolder = '',
        $plainXmlExports = false
    ) {
        $this->connectorName = Constants::CONNECTOR_NAME;
        $this->connectorVersion = Constants::CONNECTOR_VERSION;
        $this->type = (int)$type;
        $this->setUrl($url);
        $this->setWorkflow($workflow);
        $this->setProjectKey($projectKey);
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setOutFolder($outFolder);
        $this->setInFolder($inFolder);
        $this->setPlainXmlExports($plainXmlExports);
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        if ($url !== '') {
            $this->url = (string)$url;
        }
    }

    /**
     * @param string $workflow
     */
    public function setWorkflow($workflow)
    {
        if ($workflow !== '') {
            $this->workflow = (string)$workflow;
        }
    }

    /**
     * @param string $projectKey
     */
    public function setProjectKey($projectKey)
    {
        if ($projectKey !== '') {
            $this->projectKey = (string)$projectKey;
        }
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        if ($username !== '') {
            $this->username = (string)$username;
        }
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        if ($password !== '') {
            $this->password = $password;
        }
    }

    /**
     * @param string $outFolder
     */
    public function setOutFolder($outFolder)
    {
        if ($outFolder !== '') {
            $this->outFolder = trim($outFolder, '\/');
        }
    }

    /**
     * @param string $inFolder
     */
    public function setInFolder($inFolder)
    {
        if ($inFolder !== '') {
            $this->inFolder = trim($inFolder, '\/');
        }
    }

    /**
     * @param bool $plainXmlExports
     */
    public function setPlainXmlExports($plainXmlExports)
    {
        $this->plainXmlExports = $plainXmlExports;
    }

    /**
     * Sets a different connector name.
     *
     * @param string $connectorName
     */
    public function setConnectorName($connectorName)
    {
        if (is_string($connectorName)) {
            $this->connectorName = $connectorName;
        }
    }

    /**
     * Sets a different version for the connector
     *
     * @param string $connectorVersion
     */
    public function setConnectorVersion($connectorVersion)
    {
        if (is_string($connectorVersion)) {
            $this->connectorVersion = $connectorVersion;
        }
    }

    /**
     * returns a valid token if connection is established
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param $token
     */
    public function setToken($token)
    {
        if (is_string($token) && $token !== '') {
            $this->token = $token;
        }
    }

    /**
     * @return mixed
     */
    public function getLastError()
    {
        return $this->lastError ?: false;
    }

    /**
     * Will set ISO formatted deadline.
     *
     * Optional field. If not specified, the source file will not be given a specific deadline for translation.
     * If time is set equals 0 will reset deadline,
     * If time is null will get current time and adds 24hours (default) to it;
     *
     * @param null|int $time
     */
    public function setDeadline($time = null)
    {
        if ($time == 0) { // only weak check as a string will cast to 0!!!
            $this->resetDeadline();
        } else {
            if ($time === null) {
                $time = time() + $this->getDefaultDeadlineOffset();
            }
            if ($time > 0) {
                $this->deadline = date('Y-m-d\TH:i:s.u\0P', $time);
            }
        }
    }

    /**
     * resets deadline
     */
    public function resetDeadline()
    {
        $this->deadline = '';
    }

    /**
     * @return int
     */
    protected function getDefaultDeadlineOffset()
    {
        if ($this->deadlineOffset == 0 || $this->deadlineOffset < 0) {
            $this->deadlineOffset = Constants::DEADLINE_OFFSET;
        }
        return $this->deadlineOffset;
    }

    /**
     * @param int $offset
     */
    public function setDefaultDeadlineOffset($offset = 0)
    {
        $this->deadlineOffset = (int)$offset;
    }

    /**
     * Specifies one or more target languages for translation.
     * If not specified, the system assumes that the file requires translation into ALL the project target languages.
     *
     * @param array $locales
     * @throws Exception
     */
    public function setLocales(array $locales)
    {
        $this->locales = $locales;
    }

    /**
     * @param bool $asJson
     * @return string|array
     */
    public function getFolderInformation($asJson = false)
    {
        if ($this->folderInformation === null) {
            $this->folderInformation = [
                'outFolder' => $this->outFolder,
                'inFolder'  => $this->inFolder,
                'lastError' => $this->lastError,
            ];
        }
        return $asJson === true ? json_encode($this->folderInformation) : $this->folderInformation;
    }

    /**
     * @param array $metaData
     */
    public function setMetaData(array $metaData)
    {
        $this->metaData = $metaData;
    }

    /**
     * Resets instructions
     */
    public function resetInstructions()
    {
        $this->resetDeadline();
        $this->resetLocales();
        $this->resetMetaData();
    }

    /**
     * Resets locales
     */
    public function resetLocales()
    {
        $this->locales = [];
    }

    /**
     * Resets meta data
     */
    public function resetMetaData()
    {
        $this->metaData = [];
    }

    /**
     * The purpose of this method is to send original content (or files) to cost.
     *
     * @param String $fileContent The content of the file you wish to send
     * @param String $fileName Name the file will have in the Localizer
     * @param bool $attachInstructions
     * @throws Exception This Exception contains details of an eventual error
     */
    public function sandboxSendContent($fileContent, $fileName, $attachInstructions = true)
    {
        $this->sendFile($fileContent, $fileName, 'sandbox', $attachInstructions);
    }

    /**
     * Sends 1 file to the Localizer
     *
     * @param String $fileContent The content of the file you wish to send
     * @param String $fileName Name the file will have in the Localizer
     * @param string $source Source language of the file
     * @param bool $attachInstruction
     * @throws Exception
     */
    public function sendFile($fileContent, $fileName, $source, $attachInstruction = true)
    {
        switch ($this->type) {
            default:
                $this->storeFileIntoLocalHotfolder($fileContent, $fileName, $source, $attachInstruction);
        }
    }

    /**
     * Stores 1 file into the local Localizer 'out' folder
     *
     * @param String $fileContent The content of the file you wish to send
     * @param String $fileName Name the file will have in the Localizer
     * @param string $source Source language of the file
     * @throws Exception This Exception contains details of an eventual error
     */
    protected function storeFileIntoLocalHotfolder($fileContent, $fileName, $source, $attachInstruction)
    {
        if ($this->checkAndCreateFolder($this->outFolder, 'outgoing') === true) {
            $xmlPath = PATH_site . $this->outFolder . '/' . $fileName;
            if ($this->plainXmlExports) {
                $xmlFile = fopen($xmlPath, 'w') or new Exception('Can not create XML file');
                if (file_exists($xmlPath)) {
                    fwrite($xmlFile, $fileContent);
                    fclose($xmlFile);
                } else {
                    throw new Exception('Missing files for export into hotfolder');
                }
            } else {
                $zipPath = str_replace('.xml', '', $xmlPath) . '.zip';
                $zipFile = fopen($zipPath, 'w') or new Exception('Can not create ZIP file');
                $instructionFile = file_get_contents(
                    ExtensionManagementUtility::extPath(
                        'localizer'
                    ) . '/Resources/Private/Templates/Provider/instruction.xml'
                );
                if (file_exists($zipPath) && !empty($instructionFile)) {
                    $instructions = $this->getInstructions();
                    $sourceLocale = GeneralUtility::trimExplode('_', str_replace('-', '_', $source));
                    $targetLocale = GeneralUtility::trimExplode('_',
                        str_replace('-', '_', $instructions['locales'][0]));
                    $sourceLanguage = strtolower($sourceLocale[0]);
                    $sourceCountry = $sourceLocale[1] ? strtolower($sourceLocale[1]) : strtolower($sourceLocale[0]);
                    $targetLanguage = strtolower($targetLocale[0]);
                    $targetCountry = $targetLocale[1] ? strtolower($targetLocale[1]) : strtolower($targetLocale[0]);
                    $markContentArray = [
                        'DEADLINE' => $instructions['deadline'],
                        'FILE_NAME' => $fileName,
                        'PROJECT_CONTACT' => $this->getBackendUser()->user['email'],
                        'PROJECT_NAME' => date('Y-m-d') . '_Typo3CMS_' . strtoupper($sourceLanguage) . '-' . strtoupper(
                                $targetLanguage
                            ),
                        'PROJECT_SETTINGS' => $this->projectKey,
                        'SOURCE_COUNTRY' => $sourceCountry,
                        'SOURCE_LANGUAGE' => $sourceLanguage,
                        'TARGET_COUNTRY' => $targetCountry,
                        'TARGET_LANGUAGE' => $targetLanguage,
                        'WORKFLOW' => $this->workflow,
                    ];
                    $zip = new ZipArchive;
                    if ($zip->open($zipPath) === true) {
                        if ($attachInstruction) {
                            $instructionFileContent = GeneralUtility::makeInstance(
                                MarkerBasedTemplateService::class
                            )->substituteMarkerArray(
                                $instructionFile,
                                $markContentArray,
                                '###|###',
                                true,
                                true
                            );
                            $zip->addFromString('instruction.xml', $instructionFileContent);
                        }
                        $zip->addFromString($fileName, $fileContent);
                        $zip->close();
                    }
                } else {
                    throw new Exception('Missing files for export into hotfolder');
                }
            }
        }
    }

    /**
     * Checks if the folders exist or can be created if they don't exist yet
     * @param string $folder
     * @param string $type
     * @return bool
     * @throws Exception
     */
    protected function checkAndCreateFolder($folder, $type)
    {
        if ($folder) {
            $folder = PATH_site . '/' . $folder;
            if (file_exists($folder) && is_writable($folder)) {
                return true;
            } else {
                if (!file_exists($folder)) {
                    GeneralUtility::mkdir_deep($folder);
                    if (!file_exists($folder)) {
                        $this->lastError = 'Path to ' . $type . ' folder could not be created.';
                        throw new Exception($this->lastError);
                    }
                    return true;
                } else {
                    $this->lastError = 'Path to ' . $type . ' folder exists but is not writable.';
                    throw new Exception($this->lastError);
                }
            }
        } else {
            $this->lastError = 'Path to ' . $type . ' folder is missing.';
            throw new Exception($this->lastError);
        }
    }

    /**
     * @return array|bool
     */
    public function getInstructions()
    {
        $instructions = [];
        if ($this->isDeadlineSet() === true) {
            $instructions['deadline'] = $this->deadline;
        }
        if ($this->isLocalesSet() === true) {
            $instructions['locales'] = $this->locales;
        }
        if ($this->hasMetaData() === true) {
            $instructions['metadata'] = $this->metaData;
        }
        return count($instructions) > 0 ? $instructions : false;
    }

    /**
     * @return bool
     */
    protected function isDeadlineSet()
    {
        return $this->deadline !== '';
    }

    /**
     * @return bool
     */
    protected function isLocalesSet()
    {
        return count($this->locales) > 0;
    }

    /**
     * @return bool
     */
    protected function hasMetaData()
    {
        return count($this->metaData) > 0;
    }

    /**
     * @param String $fileName Name the file will have in the Localizer
     * @param string $source Source language of the file
     * @throws Exception This Exception contains details of an eventual error
     */
    public function sendInstructions($fileName, $source)
    {
        $instructions = $this->getInstructions();
        if (is_array($instructions)) {
            $content = json_encode($instructions);
            $instructionFilename = $fileName . '.localizer';
            $this->sendFile($content, $instructionFilename, $source, false);
        }
    }

    /**
     * Checks the localizer settings like url, project key, login and password.
     * By default will close connection after check.
     * If there is any existing connction at checktime this will be closed prior to check
     *
     * @return bool
     * @throws Exception
     */
    public function areSettingsValid()
    {
        return $this->checkAndCreateFolders();
    }

    /**
     * Checks if the folders exist or can be created if they don't exist yet
     * @return bool True if the folders exist and are writable
     * @throws Exception
     */
    public function checkAndCreateFolders()
    {
        if (!$this->checkAndCreateFolder($this->outFolder, 'outgoing')) {
            return false;
        }
        if (!$this->checkAndCreateFolder($this->inFolder, 'incoming')) {
            return false;
        };
        return true;
    }

}
