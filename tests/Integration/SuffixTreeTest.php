<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Systemsdk\PhpCPD\Cli\ArgumentsBuilder;
use Systemsdk\PhpCPD\CodeCloneFile;
use Systemsdk\PhpCPD\Detector\Detector;
use Systemsdk\PhpCPD\Detector\Strategy\DefaultStrategy;
use Systemsdk\PhpCPD\Detector\Strategy\StrategyConfiguration;
use Systemsdk\PhpCPD\Detector\Strategy\SuffixTreeStrategy;
use Systemsdk\PhpCPD\Detector\SuppressionGuard;

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
        $strategy = new SuffixTreeStrategy($config, new SuppressionGuard());

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
        $strategy = new DefaultStrategy($config, new SuppressionGuard());

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
        $strategy = new SuffixTreeStrategy($config, new SuppressionGuard());

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
        $strategy = new SuffixTreeStrategy($config, new SuppressionGuard());

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
        self::assertEquals(276, $file2->endLine());
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
        $strategy = new SuffixTreeStrategy($config, new SuppressionGuard());

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
        self::assertEquals(24, $file2->endLine());

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
        self::assertEquals(14, $file2->endLine());
    }

    public function testPhp84HooksIsOk(): void
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
        $strategy = new SuffixTreeStrategy($config, new SuppressionGuard());

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
        $strategy = new SuffixTreeStrategy($config, new SuppressionGuard());
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

    public function testSuppressionCase2Scenarios(): void
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
        $strategy = new SuffixTreeStrategy($config, new SuppressionGuard());
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
        self::assertCount(4, $clones->clones(), 'Failed asserting clones count');

        // Check all 4 clones
        $expectedResults = [
            [2, $path . 'Test1_B.php', $path . 'Test1_C.php', 9, 24, 9, 24],
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
