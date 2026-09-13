<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Application;

use PHPUnit\Framework\TestCase;
use Systemsdk\PhpCPD\Cli\Application;

final class ApplicationNoFilesTest extends TestCase
{
    public function testNoFilesFoundReturnsErrorCodeAndPrintsMessage(): void
    {
        $application = new Application();

        $argv = [
            'phpcpd',
            'tests/Fixture/EmptyCatalog',
        ];

        ob_start();
        $exitCode = $application->run($argv);
        $output = (string)ob_get_clean();

        // Default behavior: returns 1 and prints the message
        self::assertSame(1, $exitCode, 'Application should return error code 1 when no files are found.');
        self::assertStringContainsString('No files found to scan', $output);
    }

    public function testNoFilesFoundWithQuietFlagReturnsErrorCodeButPrintsNothing(): void
    {
        $application = new Application();

        $argv = [
            'phpcpd',
            'tests/Fixture/EmptyCatalog',
            '--quiet',
        ];

        ob_start();
        $exitCode = $application->run($argv);
        $output = (string)ob_get_clean();

        // Quiet behavior: returns 1 but standard output is completely empty
        self::assertSame(
            1,
            $exitCode,
            'Application should return error code 1 when no files are found, even in quiet mode.'
        );
        self::assertSame('', $output, 'Application output should be completely empty when the --quiet flag is used.');
    }

    public function testNoFilesFoundWithIgnoreFlagReturnsSuccessCode(): void
    {
        $application = new Application();

        $argv = [
            'phpcpd',
            'tests/Fixture/EmptyCatalog',
            '--ignore-no-files',
        ];

        ob_start();
        $exitCode = $application->run($argv);
        $output = (string)ob_get_clean();

        // Ignore flag behavior: returns 0 instead of 1
        self::assertSame(0, $exitCode, 'Application should return success code 0 due to --ignore-no-files flag.');

        // It still prints the warning message unless --quiet is also provided
        self::assertStringContainsString('No files found to scan', $output);
    }
}
