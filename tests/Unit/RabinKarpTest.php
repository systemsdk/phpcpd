<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Systemsdk\PhpCPD\Cli\ArgumentsBuilder;
use Systemsdk\PhpCPD\CodeCloneFile;
use Systemsdk\PhpCPD\Detector\Detector;
use Systemsdk\PhpCPD\Detector\Strategy\AbstractStrategy;
use Systemsdk\PhpCPD\Detector\Strategy\DefaultStrategy;
use Systemsdk\PhpCPD\Detector\Strategy\StrategyConfiguration;

use function current;
use function next;
use function sort;

final class RabinKarpTest extends TestCase
{
    #[DataProvider('strategyProvider')]
    public function testDetectingSimpleClonesWorks(AbstractStrategy $strategy): void
    {
        $clones = (new Detector($strategy))->copyPasteDetection([__DIR__ . '/../Fixture/Math.php']);

        $clones = $clones->clones();
        $files = $clones[0]->files();
        /** @var CodeCloneFile $file */
        $file = current($files);

        $this::assertSame(__DIR__ . '/../Fixture/Math.php', $file->name());
        $this::assertSame(116, $file->startLine());

        /** @var CodeCloneFile $file */
        $file = next($files);

        $this::assertSame(__DIR__ . '/../Fixture/Math.php', $file->name());
        $this::assertSame(217, $file->startLine());
        $this::assertSame(59, $clones[0]->numberOfLines());
        $this::assertSame(136, $clones[0]->numberOfTokens());

        $this::assertSame(
            '    public function div($v1, $v2)
    {
        $v3 = $v1 / ($v2 + $v1);
        if ($v3 > 14)
        {
            $v4 = 0;
            for ($i = 0; $i < $v3; $i++)
            {
                $v4 += ($v2 * $i);
            }
        }
        $v5 = ($v4 < $v3 ? ($v3 - $v4) : ($v4 - $v3));

        $v6 = ($v1 * $v2 * $v3 * $v4 * $v5);

        $d = array($v1, $v2, $v3, $v4, $v5, $v6);

        $v7 = 1;
        for ($i = 0; $i < $v6; $i++)
        {
            shuffle( $d );
            $v7 = $v7 + $i * end($d);
        }

        $v8 = $v7;
        foreach ( $d as $x )
        {
            $v8 *= $x;
        }

        $v3 = $v1 / ($v2 + $v1);
        if ($v3 > 14)
        {
            $v4 = 0;
            for ($i = 0; $i < $v3; $i++)
            {
                $v4 += ($v2 * $i);
            }
        }
        $v5 = ($v4 < $v3 ? ($v3 - $v4) : ($v4 - $v3));

        $v6 = ($v1 * $v2 * $v3 * $v4 * $v5);

        $d = array($v1, $v2, $v3, $v4, $v5, $v6);

        $v7 = 1;
        for ($i = 0; $i < $v6; $i++)
        {
            shuffle( $d );
            $v7 = $v7 + $i * end($d);
        }

        $v8 = $v7;
        foreach ( $d as $x )
        {
            $v8 *= $x;
        }

        return $v8;
',
            $clones[0]->lines()
        );
    }

    #[DataProvider('strategyProvider')]
    public function testDetectingExactDuplicateFilesWorks(AbstractStrategy $strategy): void
    {
        $argv = [
            1 => '.',
            2 => '--min-lines',
            3 => '20',
            4 => '--min-tokens',
            5 => '50',
        ];
        $arguments = (new ArgumentsBuilder())->build($argv);
        $config = new StrategyConfiguration($arguments);
        $strategy->setConfig($config);

        $clones = (new Detector($strategy))->copyPasteDetection(
            [
                __DIR__ . '/../Fixture/a.php',
                __DIR__ . '/../Fixture/b.php',
            ]
        );

        $clones = $clones->clones();
        $files = $clones[0]->files();
        /** @var CodeCloneFile $file */
        $file = current($files);

        $this::assertCount(1, $clones);
        $this::assertSame(__DIR__ . '/../Fixture/a.php', $file->name());
        $this::assertSame(7, $file->startLine());

        /** @var CodeCloneFile $file */
        $file = next($files);

        $this::assertSame(__DIR__ . '/../Fixture/b.php', $file->name());
        $this::assertSame(7, $file->startLine());
        $this::assertSame(20, $clones[0]->numberOfLines());
        $this::assertSame(60, $clones[0]->numberOfTokens());
    }

    #[DataProvider('strategyProvider')]
    public function testDetectingClonesInMoreThanTwoFiles(AbstractStrategy $strategy): void
    {
        $argv = [
            1 => '.',
            2 => '--min-lines',
            3 => '20',
            4 => '--min-tokens',
            5 => '60',
        ];
        $arguments = (new ArgumentsBuilder())->build($argv);
        $config = new StrategyConfiguration($arguments);
        $strategy->setConfig($config);

        $clones = (new Detector($strategy))->copyPasteDetection(
            [
                __DIR__ . '/../Fixture/a.php',
                __DIR__ . '/../Fixture/b.php',
                __DIR__ . '/../Fixture/c.php',
            ]
        );

        $clones = $clones->clones();
        $files = $clones[0]->files();
        sort($files);

        /** @var CodeCloneFile $file */
        $file = current($files);

        $this::assertCount(1, $clones);
        $this::assertSame(__DIR__ . '/../Fixture/a.php', $file->name());
        $this::assertSame(7, $file->startLine());

        /** @var CodeCloneFile $file */
        $file = next($files);

        $this::assertSame(__DIR__ . '/../Fixture/b.php', $file->name());
        $this::assertSame(7, $file->startLine());

        /** @var CodeCloneFile $file */
        $file = next($files);

        $this::assertSame(__DIR__ . '/../Fixture/c.php', $file->name());
        $this::assertSame(7, $file->startLine());
    }

    #[DataProvider('strategyProvider')]
    public function testClonesAreIgnoredIfTheySpanLessTokensThanMinTokens(AbstractStrategy $strategy): void
    {
        $argv = [
            1 => '.',
            2 => '--min-lines',
            3 => '24',
            4 => '--min-tokens',
            5 => '63',
        ];
        $arguments = (new ArgumentsBuilder())->build($argv);
        $config = new StrategyConfiguration($arguments);
        $strategy->setConfig($config);
        $clones = (new Detector($strategy))->copyPasteDetection(
            [
                __DIR__ . '/../Fixture/a.php',
                __DIR__ . '/../Fixture/b.php',
            ]
        );

        $this::assertCount(0, $clones->clones());
    }

    #[DataProvider('strategyProvider')]
    public function testClonesAreIgnoredIfTheySpanLessLinesThanMinLines(AbstractStrategy $strategy): void
    {
        $argv = [
            1 => '.',
            2 => '--min-lines',
            3 => '24',
            4 => '--min-tokens',
            5 => '63',
        ];
        $arguments = (new ArgumentsBuilder())->build($argv);
        $config = new StrategyConfiguration($arguments);
        $strategy->setConfig($config);
        $clones = (new Detector($strategy))->copyPasteDetection(
            [
                __DIR__ . '/../Fixture/a.php',
                __DIR__ . '/../Fixture/b.php',
            ]
        );

        $this::assertCount(0, $clones->clones());
    }

    #[DataProvider('strategyProvider')]
    public function testFuzzyClonesAreFound(AbstractStrategy $strategy): void
    {
        $argv = [
            1 => '.',
            2 => '--min-lines',
            3 => '5',
            4 => '--min-tokens',
            5 => '20',
            6 => '--fuzzy',
            7 => 'true',
        ];
        $arguments = (new ArgumentsBuilder())->build($argv);
        $config = new StrategyConfiguration($arguments);
        $strategy->setConfig($config);
        $clones = (new Detector($strategy))->copyPasteDetection(
            [
                __DIR__ . '/../Fixture/a.php',
                __DIR__ . '/../Fixture/d.php',
            ]
        );

        $this::assertCount(1, $clones->clones());
    }

    #[DataProvider('strategyProvider')]
    public function testStripComments(AbstractStrategy $strategy): void
    {
        $argv = [
            1 => '.',
            2 => '--min-lines',
            3 => '8',
            4 => '--min-tokens',
            5 => '10',
            6 => '--fuzzy',
            7 => 'true',
        ];
        $arguments = (new ArgumentsBuilder())->build($argv);
        $config = new StrategyConfiguration($arguments);
        $strategy->setConfig($config);

        $detector = new Detector($strategy);

        $clones = $detector->copyPasteDetection(
            [
                __DIR__ . '/../Fixture/e.php',
                __DIR__ . '/../Fixture/f.php',
            ]
        );

        $this::assertCount(0, $clones->clones());

        $argv = [
            1 => '.',
            2 => '--min-lines',
            3 => '7',
            4 => '--min-tokens',
            5 => '10',
            6 => '--fuzzy',
            7 => 'true',
        ];
        $arguments = (new ArgumentsBuilder())->build($argv);
        $config = new StrategyConfiguration($arguments);
        $strategy->setConfig($config);

        $clones = $detector->copyPasteDetection(
            [
                __DIR__ . '/../Fixture/e.php',
                __DIR__ . '/../Fixture/f.php',
            ],
        );

        $this::assertCount(1, $clones->clones());
    }

    /**
     * @return array<int, array<int, AbstractStrategy>>
     */
    public static function strategyProvider(): array
    {
        // Build default config.
        $argv = [
            1 => '.',
        ];
        $arguments = (new ArgumentsBuilder())->build($argv);
        $config = new StrategyConfiguration($arguments);

        return [
            [new DefaultStrategy($config)],
        ];
    }
}
