<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Cli;

use SebastianBergmann\FileIterator\Facade;
use SebastianBergmann\Timer\ResourceUsageFormatter;
use SebastianBergmann\Timer\Timer;
use SebastianBergmann\Version;
use Systemsdk\PhpCPD\Detector\Detector;
use Systemsdk\PhpCPD\Detector\Strategy\AbstractStrategy;
use Systemsdk\PhpCPD\Detector\Strategy\DefaultStrategy;
use Systemsdk\PhpCPD\Detector\Strategy\StrategyConfiguration;
use Systemsdk\PhpCPD\Detector\Strategy\SuffixTreeStrategy;
use Systemsdk\PhpCPD\Exceptions\Exception;
use Systemsdk\PhpCPD\Exceptions\InvalidStrategyException;
use Systemsdk\PhpCPD\Exceptions\LoggerException;
use Systemsdk\PhpCPD\Exceptions\ProcessingResultException;
use Systemsdk\PhpCPD\Log\PMD;
use Systemsdk\PhpCPD\Log\Text;

use function count;
use function dirname;
use function printf;

use const PHP_EOL;

final class Application
{
    public const string VERSION = '8.0.0';

    /**
     * @param array<int, string> $argv
     */
    public function run(array $argv): int
    {
        $this->printVersion();

        try {
            $arguments = (new ArgumentsBuilder())->build($argv);
        } catch (Exception $exception) {
            print PHP_EOL . $exception->getMessage() . PHP_EOL;

            return 1;
        }

        print PHP_EOL;

        if ($arguments->version()) {
            return 0;
        }

        if ($arguments->help()) {
            $this->help();

            return 0;
        }

        /** @var list<non-empty-string> $paths */
        $paths = $arguments->directories();
        /** @var list<non-empty-string> $suffixes */
        $suffixes = $arguments->suffixes();
        /** @var list<non-empty-string> $exclude */
        $exclude = $arguments->exclude();
        $files = (new Facade())->getFilesAsArray(
            $paths,
            $suffixes,
            '',
            $exclude
        );

        if (empty($files)) {
            print 'No files found to scan' . PHP_EOL;

            return 1;
        }

        try {
            $strategy = $this->pickStrategy($arguments->algorithm(), new StrategyConfiguration($arguments));
        } catch (InvalidStrategyException $exception) {
            print $exception->getMessage() . PHP_EOL;

            return 1;
        }

        $timer = new Timer();
        $timer->start();

        try {
            $clones = (new Detector($strategy))->copyPasteDetection($files);
        } catch (ProcessingResultException $exception) {
            print 'Processing error: ' . $exception->getMessage() . PHP_EOL;

            return 1;
        }

        (new Text())->printResult($clones, $arguments->verbose());

        if ($arguments->pmdCpdXmlLogfile()) {
            try {
                (new PMD($arguments->pmdCpdXmlLogfile()))->processClones($clones);
            } catch (LoggerException $exception) {
                print 'Logger error: ' . $exception->getMessage() . PHP_EOL;

                return 1;
            }
        }

        print (new ResourceUsageFormatter())->resourceUsage($timer->stop()) . PHP_EOL;

        return count($clones) > 0 ? 1 : 0;
    }

    private function printVersion(): void
    {
        /** @var non-empty-string $path */
        $path = dirname(__DIR__);
        printf('%s', (new Version(self::VERSION, $path))->asString());
    }

    /**
     * @throws InvalidStrategyException
     */
    private function pickStrategy(string $algorithm, StrategyConfiguration $config): AbstractStrategy
    {
        return match ($algorithm) {
            ArgumentsBuilder::ALGORITHM_RABIN_KARP_NAME => new DefaultStrategy($config),
            ArgumentsBuilder::ALGORITHM_SUFFIX_TREE_NAME => new SuffixTreeStrategy($config),
            default => throw new InvalidStrategyException('Unsupported algorithm: ' . $algorithm),
        };
    }

    private function help(): void
    {
        print <<<'EOT'
Usage:
  phpcpd [options] <directory>

Options for selecting files:

  --suffix <suffix> Include files with names ending on <suffix> (default: .php; can be given multiple times)
  --exclude <path>  Exclude files with <path> in their path (can be given multiple times)

Options for analysing files:

  --fuzzy           Fuzz variable names
  --min-lines <N>   Minimum number of identical lines (default: 5)
  --min-tokens <N>  Minimum number of identical tokens (default: 70)
  --verbose         Print progress bar

Options for report generation:

  --log-pmd <file>  Write log in PMD-CPD XML format to <file>

General options:

  --version         Display version
  --help            Display help

EOT;
        /**
         * TODO: check it
         * --algorithm <name>  Select which algorithm to use ('rabin-karp' (default) or 'suffixtree')
         * --edit-distance <N> Distance in number of edits between two clones (only for suffixtree; default: 5)
         * --head-equality <N> Minimum equality at start of clone (only for suffixtree; default 10)
         */
    }
}
