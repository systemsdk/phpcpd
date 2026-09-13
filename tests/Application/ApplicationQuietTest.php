<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Application;

use PHPUnit\Framework\TestCase;
use Systemsdk\PhpCPD\Cli\Application;

final class ApplicationQuietTest extends TestCase
{
    public function testQuietModeDisablesAllStandardOutput(): void
    {
        $application = new Application();

        // Running the scanner on our fixture that we know has duplicates.
        // The --quiet flag should suppress all text output.
        $argv = [
            'phpcpd',
            'tests/Fixture/Exclude',
            '--quiet',
        ];

        ob_start();
        $exitCode = $application->run($argv);
        $output = (string)ob_get_clean();

        // 1. The exit code must still be 1 because violations (duplicates) were found.
        // Quiet mode affects only the output, not the application's exit logic.
        self::assertSame(
            1,
            $exitCode,
            'Exit code should still be 1 when duplicates are found, even in quiet mode.'
        );

        // 2. The standard output must be completely empty.
        self::assertSame(
            '',
            $output,
            'Application output should be completely empty when the --quiet flag is used.'
        );
    }
}
