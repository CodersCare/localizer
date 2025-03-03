<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Api;

use Exception;
use Localizationteam\Localizer\Constants;
use Localizationteam\Localizer\Traits\BackendUserTrait;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use ZipArchive;

/**
 * ApiCalls Class used to make calls to the Localizer API
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 */
class ApiCalls implements ApiCallsInterface
{
    use BackendUserTrait;

    public string $type = '';
    protected string $connectorName = '';
    protected string $connectorVersion = '';
    protected string $token = '';
    protected string $url = '';
    protected string $projectKey = '';
    protected string $workflow = '';
    protected string $username = '';
    protected string $password = '';
    protected ?string $outFolder = null;
    protected ?string $inFolder = null;
    protected bool $plainXmlExports = false;
    protected string $deadline = '';
    protected int $deadlineOffset = 0;
    protected array $metaData = [];
    protected array $projectLanguages = [];
    protected array $locales = [];
    protected string $sourceLanguage = '';
    protected string $projectInformation = '';
    protected ?array $folderInformation = null;
    protected string $lastError = '';

    public function __construct(
        string $type,
        string $url = '',
        string $workflow = '',
        string $projectKey = '',
        string $username = '',
        string $password = '',
        string $outFolder = '',
        string $inFolder = '',
        bool $plainXmlExports = false
    ) {
        $this->connectorName = Constants::CONNECTOR_NAME;
        $this->connectorVersion = Constants::CONNECTOR_VERSION;
        $this->type = $type;
        $this->setUrl($url);
        $this->setWorkflow($workflow);
        $this->setProjectKey($projectKey);
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setOutFolder($outFolder);
        $this->setInFolder($inFolder);
        $this->setPlainXmlExports($plainXmlExports);
    }

    public function setUrl(string $url): void
    {
        if ($url !== '') {
            $this->url = $url;
        }
    }

    public function setWorkflow(string $workflow): void
    {
        if ($workflow !== '') {
            $this->workflow = $workflow;
        }
    }

    public function setProjectKey(string $projectKey): void
    {
        if ($projectKey !== '') {
            $this->projectKey = $projectKey;
        }
    }

    public function setUsername(string $username): void
    {
        if ($username !== '') {
            $this->username = $username;
        }
    }

    public function setPassword(string $password): void
    {
        if ($password !== '') {
            $this->password = $password;
        }
    }

    public function setOutFolder(string $outFolder): void
    {
        if ($outFolder !== '') {
            $this->outFolder = trim($outFolder, '\/');
        }
    }

    public function setInFolder(string $inFolder): void
    {
        if ($inFolder !== '') {
            $this->inFolder = trim($inFolder, '\/');
        }
    }

    public function setPlainXmlExports(bool $plainXmlExports): void
    {
        $this->plainXmlExports = $plainXmlExports;
    }

    /**
     * Sets a different connector name.
     */
    public function setConnectorName(string $connectorName): void
    {
        $this->connectorName = $connectorName;
    }

    /**
     * Sets a different version for the connector
     */
    public function setConnectorVersion(string $connectorVersion): void
    {
        $this->connectorVersion = $connectorVersion;
    }

    /**
     * returns a valid token if connection is established
     */
    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        if ($token !== '') {
            $this->token = $token;
        }
    }

    public function getLastError(): string
    {
        return $this->lastError ?: '';
    }

    /**
     * Will set ISO formatted deadline.
     *
     * Optional field. If not specified, the source file will not be given a specific deadline for translation.
     * If time is set equals 0 will reset deadline,
     * If time is null will get current time and adds 24hours (default) to it;
     *
     * @param mixed|null $time
     */
    public function setDeadline($time = null): void
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
    public function resetDeadline(): void
    {
        $this->deadline = '';
    }

    protected function getDefaultDeadlineOffset(): int
    {
        if ($this->deadlineOffset == 0 || $this->deadlineOffset < 0) {
            $this->deadlineOffset = Constants::DEADLINE_OFFSET;
        }
        return $this->deadlineOffset;
    }

    public function setDefaultDeadlineOffset(int $offset = 0): void
    {
        $this->deadlineOffset = $offset;
    }

    /**
     * Specifies one or more target languages for translation.
     * If not specified, the system assumes that the file requires translation into ALL the project target languages.
     *
     * @throws Exception
     */
    public function setLocales(array $locales): void
    {
        $this->locales = $locales;
    }

    /**
     * @return array|false|string
     */
    public function getFolderInformation(bool $asJson = false)
    {
        if ($this->folderInformation === null) {
            $this->folderInformation = [
                'outFolder' => $this->outFolder,
                'inFolder' => $this->inFolder,
                'lastError' => $this->lastError,
            ];
        }
        return $asJson === true ? json_encode($this->folderInformation) : $this->folderInformation;
    }

    public function setMetaData(array $metaData): void
    {
        $this->metaData = $metaData;
    }

    /**
     * Resets instructions
     */
    public function resetInstructions(): void
    {
        $this->resetDeadline();
        $this->resetLocales();
        $this->resetMetaData();
    }

    /**
     * Resets locales
     */
    public function resetLocales(): void
    {
        $this->locales = [];
    }

    /**
     * Resets meta data
     */
    public function resetMetaData(): void
    {
        $this->metaData = [];
    }

    /**
     * The purpose of this method is to send original content (or files) to cost.
     *
     * @param string $fileContent The content of the file you wish to send
     * @param string $fileName Name the file will have in the Localizer
     * @throws Exception This Exception contains details of an eventual error
     */
    public function sandboxSendContent(string $fileContent, string $fileName, bool $attachInstructions = true): void
    {
        $this->sendFile($fileContent, $fileName, 'sandbox', $attachInstructions);
    }

    /**
     * Sends 1 file to the Localizer
     *
     * @param string $fileContent The content of the file you wish to send
     * @param string $fileName Name the file will have in the Localizer
     * @param string $source Source language of the file
     * @throws Exception
     */
    public function sendFile(string $fileContent, string $fileName, string $source, bool $attachInstruction = true): void
    {
        $this->storeFileIntoLocalHotfolder($fileContent, $fileName, $source, $attachInstruction);
    }

    /**
     * Stores 1 file into the local Localizer 'out' folder
     *
     * @param string $fileContent The content of the file you wish to send
     * @param string $fileName Name the file will have in the Localizer
     * @param string $source Source language of the file
     * @throws Exception This Exception contains details of an eventual error
     */
    protected function storeFileIntoLocalHotfolder(
        string $fileContent,
        string $fileName,
        string $source,
        bool $attachInstruction
    ): void {
        if ($this->outFolder && $this->checkAndCreateFolder($this->outFolder, 'outgoing') === true) {
            $xmlPath = Environment::getPublicPath() . '/' . $this->outFolder . '/' . $fileName;
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
                    $targetLocale = GeneralUtility::trimExplode(
                        '_',
                        str_replace('-', '_', $instructions['locales'][0])
                    );
                    $sourceLanguage = strtolower($sourceLocale[0]);
                    $sourceCountry = $sourceLocale[1] ? strtolower($sourceLocale[1]) : strtolower($sourceLocale[0]);
                    $targetLanguage = strtolower($targetLocale[0]);
                    $targetCountry = $targetLocale[1] ? strtolower($targetLocale[1]) : strtolower($targetLocale[0]);
                    $markContentArray = [
                        'DEADLINE' => $instructions['deadline'] ?? '',
                        'FILE_NAME' => $fileName,
                        'PROJECT_CONTACT' => $this->getBackendUser()->user['email'] ?? '',
                        'PROJECT_NAME' => date('Y-m-d') . '_Typo3CMS_' . strtoupper($sourceLanguage) . '-' . strtoupper($targetLanguage),
                        'PROJECT_SETTINGS' => $this->projectKey,
                        'SOURCE_COUNTRY' => $sourceCountry,
                        'SOURCE_LANGUAGE' => $sourceLanguage,
                        'TARGET_COUNTRY' => $targetCountry,
                        'TARGET_LANGUAGE' => $targetLanguage,
                        'WORKFLOW' => $this->workflow,
                    ];
                    $zip = new ZipArchive();
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
     * @throws Exception
     */
    protected function checkAndCreateFolder(string $folder, string $type): bool
    {
        if ($folder) {
            $folder = Environment::getPublicPath() . '/' . $folder;
            if (file_exists($folder) && is_writable($folder)) {
                return true;
            }
            if (!file_exists($folder)) {
                GeneralUtility::mkdir_deep($folder);
                if (!file_exists($folder)) {
                    $this->lastError = 'Path to ' . $type . ' folder could not be created.';
                    throw new Exception($this->lastError);
                }
                return true;
            }
            $this->lastError = 'Path to ' . $type . ' folder exists but is not writable.';
            throw new Exception($this->lastError);
        }
        $this->lastError = 'Path to ' . $type . ' folder is missing.';
        throw new Exception($this->lastError);
    }

    /**
     * @return array|false
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

    protected function isDeadlineSet(): bool
    {
        return $this->deadline !== '';
    }

    protected function isLocalesSet(): bool
    {
        return count($this->locales) > 0;
    }

    protected function hasMetaData(): bool
    {
        return count($this->metaData) > 0;
    }

    /**
     * @param string $fileName Name the file will have in the Localizer
     * @param string $source Source language of the file
     * @throws Exception This Exception contains details of an eventual error
     */
    public function sendInstructions(string $fileName, string $source): void
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
     */
    public function areSettingsValid(): bool
    {
        return $this->checkAndCreateFolders();
    }

    /**
     * Checks if the folders exist or can be created if they don't exist yet
     * @return bool True if the folders exist and are writable
     */
    public function checkAndCreateFolders(): bool
    {
        if ($this->outFolder && !$this->checkAndCreateFolder($this->outFolder, 'outgoing')) {
            return false;
        }
        if ($this->inFolder && !$this->checkAndCreateFolder($this->inFolder, 'incoming')) {
            return false;
        }
        return true;
    }

    /**
     * The methods below are not available for the default "hot-folder" approach
     * and should be implemented for an explicit translation provider.
     */
    public function getFile(array $file): string
    {
        return '';
    }

    public function reportSuccess(array $files = [], string $target = ''): array
    {
        return [];
    }

    public function getWorkProgress(array $files = [], string $target = '', ?int $skip = 0, ?int $count = 100): array
    {
        return [];
    }
}
