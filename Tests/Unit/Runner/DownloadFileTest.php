<?php

declare(strict_types=1);

namespace Localizationteam\Localizer\Tests\Functional\Repository;

use Localizationteam\Localizer\Runner\DownloadFile;
use ReflectionClass;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DownloadFileTest extends UnitTestCase
{
    public static function adjustContentDataProvider(): array
    {
        return [
            'empty t3_targetLang' => ['<t3_targetLang translate="no"></t3_targetLang>', 'de', '<t3_targetLang translate="no">de</t3_targetLang>'],
            'pre-filled t3_targetLang' => ['<t3_targetLang translate="no">de_DE</t3_targetLang>', 'de', '<t3_targetLang translate="no">de</t3_targetLang>'],
            'without translate no' => ['<t3_targetLang></t3_targetLang>', 'de', '<t3_targetLang>de</t3_targetLang>'],
            'pre-filled t3_targetLang without translate no' => ['<t3_targetLang>de_DE</t3_targetLang>', 'de', '<t3_targetLang>de</t3_targetLang>'],
        ];
    }

    /**
     * @test
     * @dataProvider adjustContentDataProvider
     */
    public function adjustContent(string $content, string $iso2, string $expectedContent): void
    {
        $class = new ReflectionClass(DownloadFile::class);
        $adjustContent = $class->getMethod('adjustContent');

        $adjustContent->invokeArgs(new DownloadFile(), [&$content, $iso2]);
        self::assertSame($expectedContent, $content);
    }
}
