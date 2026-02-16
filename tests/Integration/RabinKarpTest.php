<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Systemsdk\PhpCPD\Cli\ArgumentsBuilder;
use Systemsdk\PhpCPD\CodeCloneFile;
use Systemsdk\PhpCPD\Detector\Detector;
use Systemsdk\PhpCPD\Detector\Strategy\AbstractStrategy;
use Systemsdk\PhpCPD\Detector\Strategy\DefaultStrategy;
use Systemsdk\PhpCPD\Detector\Strategy\StrategyConfiguration;
use Systemsdk\PhpCPD\Detector\SuppressionGuard;

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

        self::assertSame(__DIR__ . '/../Fixture/Math.php', $file->name());
        self::assertSame(116, $file->startLine());

        /** @var CodeCloneFile $file */
        $file = next($files);

        self::assertSame(__DIR__ . '/../Fixture/Math.php', $file->name());
        self::assertSame(217, $file->startLine());
        self::assertSame(59, $clones[0]->numberOfLines());
        self::assertSame(136, $clones[0]->numberOfTokens());

        self::assertSame(
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

        self::assertCount(1, $clones);
        self::assertSame(__DIR__ . '/../Fixture/a.php', $file->name());
        self::assertSame(7, $file->startLine());

        /** @var CodeCloneFile $file */
        $file = next($files);

        self::assertSame(__DIR__ . '/../Fixture/b.php', $file->name());
        self::assertSame(7, $file->startLine());
        self::assertSame(20, $clones[0]->numberOfLines());
        self::assertSame(60, $clones[0]->numberOfTokens());
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

        self::assertCount(1, $clones);
        self::assertSame(__DIR__ . '/../Fixture/a.php', $file->name());
        self::assertSame(7, $file->startLine());

        /** @var CodeCloneFile $file */
        $file = next($files);

        self::assertSame(__DIR__ . '/../Fixture/b.php', $file->name());
        self::assertSame(7, $file->startLine());

        /** @var CodeCloneFile $file */
        $file = next($files);

        self::assertSame(__DIR__ . '/../Fixture/c.php', $file->name());
        self::assertSame(7, $file->startLine());
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

        self::assertCount(0, $clones->clones());
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

        self::assertCount(0, $clones->clones());
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

        self::assertCount(1, $clones->clones());
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

        self::assertCount(0, $clones->clones());

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

        self::assertCount(1, $clones->clones());
    }

    #[DataProvider('strategyProvider')]
    public function testPhp84HooksIsOk(AbstractStrategy $strategy): void
    {
        $argv = [
            1 => '.',
            2 => '--min-lines',
            3 => '5',
            4 => '--min-tokens',
            5 => '30',
        ];
        $arguments = (new ArgumentsBuilder())->build($argv);
        $config = new StrategyConfiguration($arguments);
        $strategy->setConfig($config);

        $clones = (new Detector($strategy))->copyPasteDetection(
            [
                __DIR__ . '/../Fixture/php84_hooks.php',
            ]
        );

        $clones = $clones->clones();
        $files = $clones[0]->files();
        /** @var CodeCloneFile $file1 */
        $file1 = current($files);
        /** @var CodeCloneFile $file2 */
        $file2 = next($files);

        self::assertCount(1, $clones);

        self::assertSame(__DIR__ . '/../Fixture/php84_hooks.php', $file1->name());
        self::assertSame(7, $file1->startLine());
        self::assertSame(19, $file1->endLine());

        self::assertSame(__DIR__ . '/../Fixture/php84_hooks.php', $file2->name());
        self::assertSame(25, $file2->startLine());
        self::assertSame(37, $file2->endLine());
    }

    #[DataProvider('suppressionFixturesProvider')]
    public function testSuppressionCase1Scenarios(
        string $file,
        int $expectedClones,
        int $headStartLine,
        int $headEndLine,
        int $otherStartLine,
        int $otherEndLine
    ): void {
        $argv = [
            1 => '.',
            2 => '--min-lines',
            3 => '3',
            4 => '--min-tokens',
            5 => '10',
        ];
        $arguments = (new ArgumentsBuilder())->build($argv);
        $config = new StrategyConfiguration($arguments);
        $strategy = new DefaultStrategy($config, new SuppressionGuard());
        $clones = (new Detector($strategy))->copyPasteDetection([$file]);

        self::assertCount(
            $expectedClones,
            $clones->clones(),
            'Failed asserting clone count for file: ' . basename($file)
        );

        if ($expectedClones === 0) {
            return;
        }

        $foundFiles = $clones->clones()[0]->files();
        self::assertCount(2, $foundFiles);
        /** @var CodeCloneFile $file1 */
        $file1 = current($foundFiles);
        /** @var CodeCloneFile $file2 */
        $file2 = next($foundFiles);
        self::assertSame($file, $file1->name());
        self::assertSame($headStartLine, $file1->startLine());
        self::assertSame($headEndLine, $file1->endLine());
        self::assertSame($file, $file2->name());
        self::assertSame($otherStartLine, $file2->startLine());
        self::assertSame($otherEndLine, $file2->endLine());
    }

    #[DataProvider('strategyProvider')]
    public function testSuppressionCase2Scenarios(AbstractStrategy $strategy): void
    {
        $argv = [
            1 => '.',
            2 => '--min-lines',
            3 => '7',
            4 => '--min-tokens',
            5 => '10',
        ];
        $arguments = (new ArgumentsBuilder())->build($argv);
        $config = new StrategyConfiguration($arguments);
        $strategy->setConfig($config);
        $path = __DIR__ . '/../Fixture/SuppressCase2/';
        $clones = (new Detector($strategy))->copyPasteDetection([
            $path . 'Test1_A.php',
            $path . 'Test1_B.php',
            $path . 'Test1_C.php',
            $path . 'Test2_A.php',
            $path . 'Test2_B.php',
            $path . 'Test2_C.php',
            $path . 'Test3_A.php',
            $path . 'Test3_B.php',
            $path . 'Test3_C.php',
            $path . 'Test4_A.php',
            $path . 'Test4_B.php',
        ]);
        self::assertCount(5, $clones->clones(), 'Failed asserting clones count');

        // Check all 5 clones
        $expectedResults = [
            [2, $path . 'Test1_B.php', $path . 'Test1_C.php', 9, 24, 9, 24],
            [2, $path . 'Test2_A.php', $path . 'Test2_C.php', 9, 22, 9, 22],
            [2, $path . 'Test2_A.php', $path . 'Test2_C.php', 12, 24, 23, 35],
            [2, $path . 'Test3_C.php', $path . 'Test3_C.php', 11, 24, 24, 37],
            [2, $path . 'Test4_A.php', $path . 'Test4_B.php', 17, 33, 14, 30],
        ];
        $i = 0;
        foreach ($clones->clones() as $clone) {
            $foundFiles = $clone->files();
            self::assertCount($expectedResults[$i][0], $foundFiles);
            /** @var CodeCloneFile $file1 */
            $file1 = current($foundFiles);
            /** @var CodeCloneFile $file2 */
            $file2 = next($foundFiles);
            self::assertSame($expectedResults[$i][1], $file1->name());
            self::assertSame($expectedResults[$i][3], $file1->startLine());
            self::assertSame($expectedResults[$i][4], $file1->endLine());
            self::assertSame($expectedResults[$i][2], $file2->name());
            self::assertSame($expectedResults[$i][5], $file2->startLine());
            self::assertSame($expectedResults[$i][6], $file2->endLine());
            $i++;
        }
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
            [new DefaultStrategy($config, new SuppressionGuard())],
        ];
    }

    /**
     * @return array<string, array<int, int|string>>
     */
    public static function suppressionFixturesProvider(): array
    {
        $path = __DIR__ . '/../Fixture/SuppressCase1/';

        return [
            'Method Scope' => [$path . 'Test1_method.php', 0, 0, 0, 0, 0],
            'Class Scope' => [$path . 'Test2_class.php', 1, 33, 40, 42, 49],
            'Complex Syntax' => [$path . 'Test3_complex.php', 0, 0, 0, 0, 0],
            'Mixed Content' => [$path . 'Test4_mixed.php', 1, 21, 28, 42, 49],
            'PHP 8.4 Hooks' => [$path . 'Test5_php84.php', 0, 0, 0, 0, 0],
        ];
    }
}
