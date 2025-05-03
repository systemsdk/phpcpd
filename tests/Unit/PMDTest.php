<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Unit;

use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Systemsdk\PhpCPD\Cli\Application;
use Systemsdk\PhpCPD\CodeClone;
use Systemsdk\PhpCPD\CodeCloneFile;
use Systemsdk\PhpCPD\CodeCloneMap;
use Systemsdk\PhpCPD\Log\PMD;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function strtr;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class PMDTest extends TestCase
{
    private string $testFile1;
    private string $testFile2;
    private string $pmdLogFile;
    private string $expectedPmdLogFile;
    private PMD $pmdLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testFile1 = __DIR__ . '/../Fixture/with_ascii_escape.php';
        $this->testFile2 = __DIR__ . '/../Fixture/with_ascii_escape2.php';
        $pmdLogFile = tempnam(sys_get_temp_dir(), 'pmd');
        self::assertIsString($pmdLogFile, 'Can not create pmd log file');
        $this->pmdLogFile = $pmdLogFile;
        $expectedPmdLogFile = tempnam(sys_get_temp_dir(), 'pmd');
        self::assertIsString($expectedPmdLogFile, 'Can not create expected pmd log file');
        $this->expectedPmdLogFile = $expectedPmdLogFile;
        $expectedPmdLogTemplate = file_get_contents(__DIR__ . '/../Fixture/pmd_expected.xml');
        self::assertIsString($expectedPmdLogTemplate, 'Can not get expected pmd log template');
        $expectedPmdLogContents = strtr(
            $expectedPmdLogTemplate,
            [
                '%version%' => Application::VERSION,
                '%datetime%' => (new DateTime())->format(DateTimeInterface::ATOM),
                '%file1%' => $this->testFile1,
                '%file2%' => $this->testFile2,
            ]
        );
        self::assertIsInt(
            file_put_contents($this->expectedPmdLogFile, $expectedPmdLogContents),
            'Can not write into expected pmd log file'
        );
        $this->pmdLogger = new PMD($this->pmdLogFile);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->pmdLogFile)) {
            unlink($this->pmdLogFile);
        }

        if (file_exists($this->expectedPmdLogFile)) {
            unlink($this->expectedPmdLogFile);
        }
    }

    public function testSubstitutesDisallowedCharacters(): void
    {
        $file1 = new CodeCloneFile($this->testFile1, 8, 8 + 4);
        $file2 = new CodeCloneFile($this->testFile2, 8, 8 + 4);
        $clone = new CodeClone($file1, $file2, 4, 4);
        $cloneMap = new CodeCloneMap();
        $cloneMap->add($clone);
        $this->pmdLogger->processClones($cloneMap);

        self::assertXmlFileEqualsXmlFile($this->expectedPmdLogFile, $this->pmdLogFile);
    }
}
