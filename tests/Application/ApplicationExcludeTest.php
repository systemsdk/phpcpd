<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Application;

use PHPUnit\Framework\TestCase;
use Systemsdk\PhpCPD\Cli\Application;

final class ApplicationExcludeTest extends TestCase
{
    private string $reportPath;

    protected function setUp(): void
    {
        parent::setUp();

        $reportDir = getcwd() . '/reports/phpcpd';
        $this->reportPath = $reportDir . '/phpcpd-exclude-report.json';

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

    public function testExcludeExactSegmentMatching(): void
    {
        $application = new Application();

        $argv = [
            'phpcpd',
            'tests/Fixture/Exclude',
            '--exclude', 'Vendor',
            '--log-json=' . $this->reportPath,
        ];

        ob_start();
        $application->run($argv);
        ob_get_clean();

        self::assertFileExists($this->reportPath);

        $jsonOutput = (string)file_get_contents($this->reportPath);

        // 'Vendor' must be entirely missing from the results
        self::assertStringNotContainsString('Vendor/', $jsonOutput);

        // 'Vendor2' must be present because exact segment matching prevents accidental exclusion
        self::assertStringContainsString('Vendor2/', $jsonOutput);

        // 'Src' must also be present
        self::assertStringContainsString('Src/', $jsonOutput);
    }

    public function testMultipleExcludesAndNestedPaths(): void
    {
        $application = new Application();

        $argv = [
            'phpcpd',
            'tests/Fixture/Exclude',
            '--exclude', 'Vendor',
            '--exclude', 'App/Services',
            '--log-json=' . $this->reportPath,
        ];

        ob_start();
        $application->run($argv);
        ob_get_clean();

        self::assertFileExists($this->reportPath);

        $jsonOutput = (string)file_get_contents($this->reportPath);

        // Both explicitly excluded paths should be missing
        self::assertStringNotContainsString('Vendor/', $jsonOutput);
        self::assertStringNotContainsString('App/Services/', $jsonOutput);

        // The remaining valid paths should still be scanned and present in the report
        self::assertStringContainsString('Vendor2/', $jsonOutput);
        self::assertStringContainsString('Src/', $jsonOutput);
    }

    public function testExcludeIndividualFile(): void
    {
        $application = new Application();

        // Testing the exclusion of a specific file as mentioned in the docs
        $argv = [
            'phpcpd',
            'tests/Fixture/Exclude',
            '--exclude', 'Src/FileA.php',
            '--log-json=' . $this->reportPath,
        ];

        ob_start();
        $application->run($argv);
        ob_get_clean();

        self::assertFileExists($this->reportPath);

        $jsonOutput = (string)file_get_contents($this->reportPath);

        // The specific excluded file must be missing
        self::assertStringNotContainsString('Src/FileA.php', $jsonOutput);

        // However, other files in the same directory must still be scanned and present
        self::assertStringContainsString('Src/FileB.php', $jsonOutput);
    }
}
