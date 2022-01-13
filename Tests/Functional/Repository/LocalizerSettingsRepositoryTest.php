<?php

namespace Localizationteam\Localizer\Tests\Functional\Repository;

use Localizationteam\Localizer\Model\Repository\LocalizerSettingsRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class LocalizerSettingsRepositoryTest extends FunctionalTestCase
{
    protected ObjectManagerInterface $objectManager;

    protected LocalizerSettingsRepository $repository;

    protected $testExtensionsToLoad = [
        'typo3conf/ext/localizer',
        'typo3conf/ext/l10nmgr',
        'typo3conf/ext/static_info_tables',
    ];

    protected $coreExtensionsToLoad = [
        'install',
        'scheduler',
        'extensionmanager',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->setUpBackendUserFromFixture(1);
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->repository = $this->objectManager->get(LocalizerSettingsRepository::class);

        $this->importDataSet(__DIR__ . '/../Fixtures/tx_localizer_settings.xml');
    }

    /**
     * @test
     */
    public function findRecordsByUid()
    {
        $localizerSettings = $this->repository->findByUid(1);
        self::assertEquals('Beebox', $localizerSettings['title']);
    }

    /**
     * @test
     */
    public function findAllFindsAll(): void
    {
        $localizers = $this->repository->findAll();

        self::assertCount(3, $localizers);
        self::assertIsArray($localizers);
    }

    /**
     * @test
     */
    public function loadAvailableLocalizersFindsAvailableLocalizers(): void
    {
        $availableLocalizers = $this->repository->loadAvailableLocalizers();
        foreach ($availableLocalizers as $uid => $availableLocalizer) {
            self::assertEquals($uid, $availableLocalizer['uid']);
        }

        self::assertEquals('Beebox', $availableLocalizers[1]['title']);
        self::assertEquals('HotFolders', $availableLocalizers[2]['title']);
    }

    /**
     * @test
     */
    public function getLocalizerLanguagesWithWrongLocalizerIdHasNullValues(): void
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_localizer_language_mm.xml');

        $languages = $this->repository->getLocalizerLanguages(1);
        self::assertArrayHasKey('source', $languages);
        self::assertArrayHasKey('target', $languages);

        self::assertNull($languages['source']);
        self::assertNull($languages['target']);
    }

    /**
     * @test
     */
    public function getLocalizerLanguages(): void
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_localizer_language_mm.xml');

        $languages = $this->repository->getLocalizerLanguages(3);

        self::assertArrayHasKey('source', $languages);
        self::assertArrayHasKey('target', $languages);

        self::assertEquals(30, $languages['source']);
        self::assertEquals('37,59', $languages['target']);
    }

    public function tearDown(): void
    {
        unset($this->repository);
        unset($this->objectManager);
    }
}
