<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Application;

use PHPUnit\Framework\TestCase;
use Systemsdk\PhpCPD\Cli\Application;

final class ApplicationPipelineControlsTest extends TestCase
{
    public function testBuildFailsWhenMaxPercentageIsExceeded(): void
    {
        $application = new Application();

        // We know the fixture generates 67.44% duplicated lines. Set the limit to 50% to trigger a build failure.
        $argv = [
            'phpcpd',
            'tests/Fixture/Exclude',
            '--max-percentage=50',
        ];

        ob_start();
        $exitCode = $application->run($argv);
        $output = (string)ob_get_clean();

        // Verify that the application returns an error code (1)
        self::assertSame(
            1,
            $exitCode,
            'Application should return error code 1 when duplication exceeds max-percentage.'
        );

        // Ensure the parser actually ran and output the real percentage
        self::assertStringContainsString('67.44% duplicated lines', $output);
    }

    public function testBuildPassesWhenMaxPercentageIsNotExceeded(): void
    {
        $application = new Application();

        // Set the limit to 80%, which is higher than the actual 67.44%. The build should pass.
        $argv = [
            'phpcpd',
            'tests/Fixture/Exclude',
            '--max-percentage=80',
        ];

        ob_start();
        $exitCode = $application->run($argv);
        ob_get_clean();

        self::assertSame(
            0,
            $exitCode,
            'Application should return success code 0 when duplication is under max-percentage.'
        );
    }

    public function testIgnoreViolationsOnExitForcesSuccessCode(): void
    {
        $application = new Application();

        // By default, found duplicates cause an error exit code (1).
        // Add the --ignore-violations-on-exit flag to force the application to return 0.
        $argv = [
            'phpcpd',
            'tests/Fixture/Exclude',
            '--ignore-violations-on-exit',
        ];

        ob_start();
        $exitCode = $application->run($argv);
        $output = (string)ob_get_clean();

        self::assertSame(
            0,
            $exitCode,
            'Application should return success code 0 even if duplicates are found, due to the ignore flag.'
        );

        // Verify that duplicates were actually found (the tool didn't just skip scanning)
        self::assertStringContainsString('Found 2 code clones', $output);
    }
}
