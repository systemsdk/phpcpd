<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Systemsdk\PhpCPD\Cli\ArgumentsBuilder;
use Systemsdk\PhpCPD\Detector\Detector;
use Systemsdk\PhpCPD\Detector\Strategy\DefaultStrategy;
use Systemsdk\PhpCPD\Detector\Strategy\StrategyConfiguration;
use Systemsdk\PhpCPD\Detector\Strategy\SuffixTreeStrategy;

final class EditDistanceTest extends TestCase
{
    public function testEditDistanceWithSuffixtree(): void
    {
        $argv = [
            1 => '.',
            2 => '--min-tokens',
            3 => '60',
        ];
        $arguments = (new ArgumentsBuilder())->build($argv);
        $config = new StrategyConfiguration($arguments);
        $strategy = new SuffixTreeStrategy($config);

        $clones = (new Detector($strategy))->copyPasteDetection(
            [
                __DIR__ . '/../Fixture/editdistance1.php',
                __DIR__ . '/../Fixture/editdistance2.php',
            ],
        );

        $clones = $clones->clones();
        self::assertCount(1, $clones);
    }

    public function testEditDistanceWithRabinkarp(): void
    {
        $argv = [
            1 => '.',
            2 => '--min-tokens',
            3 => '60',
        ];
        $arguments = (new ArgumentsBuilder())->build($argv);
        $config = new StrategyConfiguration($arguments);
        $strategy = new DefaultStrategy($config);

        $clones = (new Detector($strategy))->copyPasteDetection(
            [
                __DIR__ . '/../Fixture/editdistance1.php',
                __DIR__ . '/../Fixture/editdistance2.php',
            ],
        );

        $clones = $clones->clones();
        self::assertCount(0, $clones);
    }
}
