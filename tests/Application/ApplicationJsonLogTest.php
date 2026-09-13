<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Application;

use JsonException;
use PHPUnit\Framework\TestCase;
use Systemsdk\PhpCPD\Cli\Application;

final class ApplicationJsonLogTest extends TestCase
{
    private string $reportPath;
    private string $expectedPath;

    protected function setUp(): void
    {
        parent::setUp();

        $reportDir = getcwd() . '/reports/phpcpd';
        $this->reportPath = $reportDir . '/phpcpd-report.json';
        $this->expectedPath = getcwd() . '/tests/Fixture/Log/json_expected.json';

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
    public function testGsonLogGenerationMatchesExpectedOutput(): void
    {
        $application = new Application();

        $argv = [
            'phpcpd',
            'tests/Fixture/Log',
            '--log-json=' . $this->reportPath,
        ];

        ob_start();
        $application->run($argv);
        ob_get_clean();

        self::assertFileExists($this->reportPath);

        // Decode JSON from files into associative arrays
        $expectedData = json_decode((string)file_get_contents($this->expectedPath), true, flags: JSON_THROW_ON_ERROR);
        $actualData = json_decode((string)file_get_contents($this->reportPath), true, flags: JSON_THROW_ON_ERROR);

        // Assert that the keys exist before unsetting them (this ensures the logger structure remains intact)
        self::assertArrayHasKey('timestamp', $actualData['metadata']);
        self::assertArrayHasKey('version', $actualData['metadata']);

        // Remove dynamic fields from both arrays
        unset(
            $expectedData['metadata']['timestamp'],
            $expectedData['metadata']['version'],
            $actualData['metadata']['timestamp'],
            $actualData['metadata']['version']
        );

        // Compare the sanitized data
        self::assertEquals($expectedData, $actualData);
    }
}
