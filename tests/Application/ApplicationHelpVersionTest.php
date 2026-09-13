<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Application;

use PHPUnit\Framework\TestCase;
use Systemsdk\PhpCPD\Cli\Application;

final class ApplicationHelpVersionTest extends TestCase
{
    public function testInvalidArgumentTriggersExceptionAndReturnsErrorCode(): void
    {
        $application = new Application();

        // Testing the catch (Exception) block for invalid arguments
        $argv = [
            'phpcpd',
            '--an-invalid-flag-that-does-not-exist',
        ];

        ob_start();
        $exitCode = $application->run($argv);
        $output = (string)ob_get_clean();

        // Must return 1 according to line 46 in the screenshot
        self::assertSame(1, $exitCode, 'Application should return error code 1 for invalid arguments.');

        // Output must contain the version string (line 43)
        self::assertStringContainsString('Copy/Paste Detector', $output);
    }

    public function testVersionFlagReturnsSuccessCode(): void
    {
        $application = new Application();

        // Testing the $arguments->version() block
        $argv = [
            'phpcpd',
            '--version',
        ];

        ob_start();
        $exitCode = $application->run($argv);
        $output = (string)ob_get_clean();

        // Must return 0 according to line 55 in the screenshot
        self::assertSame(0, $exitCode, 'Application should return success code 0 for --version flag.');

        // Output must contain the version string (printed on line 50 before the version check)
        self::assertStringContainsString('Copy/Paste Detector', $output);
    }

    public function testHelpFlagReturnsSuccessCodeAndDisplaysHelpText(): void
    {
        $application = new Application();

        // Testing the $arguments->help() block
        $argv = [
            'phpcpd',
            '--help',
        ];

        ob_start();
        $exitCode = $application->run($argv);
        $output = (string)ob_get_clean();

        // Must return 0 according to line 61 in the screenshot
        self::assertSame(0, $exitCode, 'Application should return success code 0 for --help flag.');

        // Output must contain the version string and some help instructions
        self::assertStringContainsString('Copy/Paste Detector', $output);
        self::assertStringContainsString('Usage:', $output);
        self::assertStringContainsString('options:', $output);
    }
}
