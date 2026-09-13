<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Application;

use JsonException;
use PHPUnit\Framework\TestCase;
use Systemsdk\PhpCPD\Cli\Application;

final class ApplicationSarifLogTest extends TestCase
{
    private string $reportPath;
    private string $expectedPath;

    protected function setUp(): void
    {
        parent::setUp();

        $reportDir = getcwd() . '/reports/phpcpd';
        $this->reportPath = $reportDir . '/phpcpd-report.sarif';
        $this->expectedPath = getcwd() . '/tests/Fixture/Log/sarif_expected.sarif';

        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0777, true);
        }

        if (file_exists($this->reportPath)) {
            unlink($this->reportPath);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->reportPath)) {
            unlink($this->reportPath);
        }
    }

    /**
     * @throws JsonException
     */
    public function testSarifLogGenerationMatchesExpectedOutput(): void
    {
        $application = new Application();

        $argv = [
            'phpcpd',
            'tests/Fixture/Log',
            '--log-sarif=' . $this->reportPath,
        ];

        ob_start();
        $application->run($argv);
        ob_get_clean();

        self::assertFileExists($this->reportPath);

        // Decode JSON from files into associative arrays
        $expectedData = json_decode((string)file_get_contents($this->expectedPath), true, flags: JSON_THROW_ON_ERROR);
        $actualData = json_decode((string)file_get_contents($this->reportPath), true, flags: JSON_THROW_ON_ERROR);

        // Assert that the semanticVersion key exists before unsetting it to ensure the schema structure is not broken
        self::assertArrayHasKey('semanticVersion', $actualData['runs'][0]['tool']['driver']);

        // Remove the dynamic version field from both arrays
        unset(
            $expectedData['runs'][0]['tool']['driver']['semanticVersion'],
            $actualData['runs'][0]['tool']['driver']['semanticVersion']
        );

        // Compare the sanitized data
        self::assertEquals($expectedData, $actualData);
    }
}
