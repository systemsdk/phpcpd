<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Application;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use Systemsdk\PhpCPD\Cli\Application;

final class ApplicationConfigAndSuppressTest extends TestCase
{
    private string $configPath;
    private string $reportPath;
    private string $expectedPath;

    protected function setUp(): void
    {
        parent::setUp();

        $configDistPath = getcwd() . '/phpcpd.config.php.dist';
        $this->configPath = getcwd() . '/phpcpd.config.php';

        $reportDir = getcwd() . '/reports/phpcpd';
        $this->reportPath = $reportDir . '/phpcpd-report.xml';

        // Corrected path typos to match standard project structure
        $this->expectedPath = getcwd() . '/tests/Fixture/Log/pmd_expected2.xml';

        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0777, true);
        }

        // Clean up previous runs
        if (file_exists($this->reportPath)) {
            unlink($this->reportPath);
        }
        if (file_exists($this->configPath)) {
            unlink($this->configPath);
        }

        // Create the config file from the dist template
        if (file_exists($configDistPath)) {
            copy($configDistPath, $this->configPath);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up generated files after the test
        if (file_exists($this->reportPath)) {
            unlink($this->reportPath);
        }

        if (file_exists($this->configPath)) {
            unlink($this->configPath);
        }
    }

    public function testConfigLoadingAndSuppressCpdAuditInPmdReport(): void
    {
        // Ensure the config file was actually created successfully before running
        self::assertFileExists($this->configPath);

        $application = new Application();

        $argv = [
            'phpcpd',
            'tests/Fixture/SuppressCase1',
            '--log-pmd=' . $this->reportPath,
        ];

        ob_start();
        $application->run($argv);
        ob_get_clean();

        self::assertFileExists($this->reportPath);

        // Read and sanitize XML to remove dynamic timestamp and version attributes
        $expectedXml = $this->sanitizeXml((string)file_get_contents($this->expectedPath));
        $actualXml = $this->sanitizeXml((string)file_get_contents($this->reportPath));

        // Use strict XML string comparison (ignores whitespace and formatting)
        self::assertXmlStringEqualsXmlString($expectedXml, $actualXml);
    }

    /**
     * Removes dynamic attributes (phpcpdVersion, timestamp) from the root XML node to prevent test failures during
     * new version releases.
     */
    private function sanitizeXml(string $xmlContent): string
    {
        $dom = new DOMDocument();
        $dom->loadXML($xmlContent);

        $rootNode = $dom->documentElement;

        if ($rootNode !== null) {
            if ($rootNode->hasAttribute('phpcpdVersion')) {
                $rootNode->removeAttribute('phpcpdVersion');
            }
            if ($rootNode->hasAttribute('timestamp')) {
                $rootNode->removeAttribute('timestamp');
            }
        }

        return (string)$dom->saveXML();
    }
}
