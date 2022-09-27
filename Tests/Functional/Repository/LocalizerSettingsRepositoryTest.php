<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Tests\Functional\Repository;

use Doctrine\DBAL\DBALException;
use Localizationteam\Localizer\Model\Repository\LocalizerSettingsRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\TestingFramework\Core\Exception;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class LocalizerSettingsRepositoryTest extends FunctionalTestCase
{
    /**
     * @var ObjectManagerInterface
     */
    protected mixed $objectManager;

    /**
     * @var LocalizerSettingsRepository
     */
    protected mixed $repository;

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

    /**
     * @throws Exception
     * @throws DBALException
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    protected function setUp(): void
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
        try {
            $this->importDataSet(__DIR__ . '/../Fixtures/tx_localizer_language_mm.xml');
        } catch (Exception $e) {
        }

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
        try {
            $this->importDataSet(__DIR__ . '/../Fixtures/tx_localizer_language_mm.xml');
        } catch (Exception $e) {
        }

        $languages = $this->repository->getLocalizerLanguages(3);

        self::assertArrayHasKey('source', $languages);
        self::assertArrayHasKey('target', $languages);

        self::assertEquals(30, $languages['source']);
        self::assertEquals('37,59', $languages['target']);
    }

    protected function tearDown(): void
    {
        unset($this->repository);
        unset($this->objectManager);
    }
}
