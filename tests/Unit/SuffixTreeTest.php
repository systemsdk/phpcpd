<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Systemsdk\PhpCPD\Cli\ArgumentsBuilder;
use Systemsdk\PhpCPD\Detector\Detector;
use Systemsdk\PhpCPD\Detector\Strategy\DefaultStrategy;
use Systemsdk\PhpCPD\Detector\Strategy\StrategyConfiguration;
use Systemsdk\PhpCPD\Detector\Strategy\SuffixTreeStrategy;

final class SuffixTreeTest extends TestCase
{
    public function testEditDistanceAndValue5WithSuffixtreeReturnsClones(): void
    {
        $argv = [
            1 => '.',
            2 => '--edit-distance',
            3 => '5',
            4 => '--min-tokens',
            5 => '60',
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
        self::assertEquals(27, $clones[0]->numberOfLines());
        self::assertEquals(77, $clones[0]->numberOfTokens());

        // let's check files
        $files = $clones[0]->files();
        self::assertCount(2, $files);
    }

    public function testNoEditDistanceWithRabinkarpReturnsNoClones(): void
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

    public function testNoEditDistanceWithSuffixtreeReturnsNoClones(): void
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
        self::assertCount(0, $clones);
    }

    public function testNoEditDistanceWithSuffixtreeReturns1Clone(): void
    {
        $argv = [
            1 => '.',
            2 => '--min-tokens',
            3 => '30',
        ];
        $arguments = (new ArgumentsBuilder())->build($argv);
        $config = new StrategyConfiguration($arguments);
        $strategy = new SuffixTreeStrategy($config);

        $clones = (new Detector($strategy))->copyPasteDetection(
            [
                __DIR__ . '/../Fixture/Math.php',
            ],
        );

        $clones = $clones->clones();
        self::assertCount(1, $clones);

        // let's check clone
        $clone = $clones[0];
        self::assertEquals(59, $clone->numberOfLines());
        self::assertEquals(136, $clone->numberOfTokens());
        $files = $clone->files();
        self::assertCount(2, $files);
        self::assertArrayHasKey(__DIR__ . '/../Fixture/Math.php:116', $files);
        self::assertArrayHasKey(__DIR__ . '/../Fixture/Math.php:217', $files);
        $file1 = $files[__DIR__ . '/../Fixture/Math.php:116'];
        self::assertEquals(__DIR__ . '/../Fixture/Math.php', $file1->name());
        self::assertEquals(116, $file1->startLine());
        $file2 = $files[__DIR__ . '/../Fixture/Math.php:217'];
        self::assertEquals(__DIR__ . '/../Fixture/Math.php', $file2->name());
        self::assertEquals(217, $file2->startLine());
        self::assertEquals(275, $file2->endLine());
    }

    public function testNoEditDistanceWithSuffixtreeReturns2Clones(): void
    {
        $argv = [
            1 => '.',
            2 => '--min-tokens',
            3 => '30',
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
        self::assertCount(2, $clones);

        // let's check first clone
        $firstClone = $clones[0];
        self::assertEquals(10, $firstClone->numberOfLines());
        self::assertEquals(43, $firstClone->numberOfTokens());
        $files = $firstClone->files();
        self::assertCount(2, $files);
        self::assertArrayHasKey(__DIR__ . '/../Fixture/editdistance1.php:16', $files);
        self::assertArrayHasKey(__DIR__ . '/../Fixture/editdistance2.php:14', $files);
        $file1 = $files[__DIR__ . '/../Fixture/editdistance1.php:16'];
        self::assertEquals(__DIR__ . '/../Fixture/editdistance1.php', $file1->name());
        self::assertEquals(16, $file1->startLine());
        $file2 = $files[__DIR__ . '/../Fixture/editdistance2.php:14'];
        self::assertEquals(__DIR__ . '/../Fixture/editdistance2.php', $file2->name());
        self::assertEquals(14, $file2->startLine());
        self::assertEquals(23, $file2->endLine());

        // let's check second clone
        $secondClone = $clones[1];
        self::assertEquals(13, $secondClone->numberOfLines());
        self::assertEquals(32, $secondClone->numberOfTokens());
        $files = $secondClone->files();
        self::assertCount(2, $files);
        self::assertArrayHasKey(__DIR__ . '/../Fixture/editdistance1.php:3', $files);
        self::assertArrayHasKey(__DIR__ . '/../Fixture/editdistance2.php:3', $files);
        $file1 = $files[__DIR__ . '/../Fixture/editdistance1.php:3'];
        self::assertEquals(__DIR__ . '/../Fixture/editdistance1.php', $file1->name());
        self::assertEquals(3, $file1->startLine());
        $file2 = $files[__DIR__ . '/../Fixture/editdistance2.php:3'];
        self::assertEquals(__DIR__ . '/../Fixture/editdistance2.php', $file2->name());
        self::assertEquals(3, $file2->startLine());
        self::assertEquals(13, $file2->endLine());
    }
}
