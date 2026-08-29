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
use Systemsdk\PhpCPD\Detector\SuppressionGuard;
use Systemsdk\PhpCPD\Exceptions\Exception;
use Systemsdk\PhpCPD\Exceptions\InvalidStrategyException;
use Systemsdk\PhpCPD\Exceptions\LoggerException;
use Systemsdk\PhpCPD\Exceptions\ProcessingResultException;
use Systemsdk\PhpCPD\Log\Json;
use Systemsdk\PhpCPD\Log\PMD;
use Systemsdk\PhpCPD\Log\Sarif;
use Systemsdk\PhpCPD\Log\Text;

use function count;
use function dirname;
use function printf;

use const PHP_EOL;

final class Application
{
    public const string VERSION = '9.1.0';

    /**
     * @param array<int, string> $argv
     */
    public function run(array $argv): int
    {
        $this->printVersion();

        try {
            $arguments = new ArgumentsBuilder()->build($argv);
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
        $files = $this->filterExcludedFiles(
            new Facade()->getFilesAsArray(
                $paths,
                $suffixes,
                '',
                $exclude
            ),
            $exclude
        );

        if (empty($files)) {
            print 'No files found to scan' . PHP_EOL;

            if ($arguments->ignoreNoFiles()) {
                return 0;
            }

            return 1;
        }

        try {
            $strategy = $this->pickStrategy(
                $arguments->algorithm(),
                new StrategyConfiguration($arguments),
                new SuppressionGuard()
            );
        } catch (InvalidStrategyException $exception) {
            print $exception->getMessage() . PHP_EOL;

            return 1;
        }

        $timer = new Timer();
        $timer->start();

        try {
            $clones = new Detector($strategy, true)->copyPasteDetection($files);
        } catch (ProcessingResultException $exception) {
            print 'Processing error: ' . $exception->getMessage() . PHP_EOL;

            return 1;
        }

        new Text()->printResult($clones, $arguments->verbose());

        if ($arguments->pmdCpdXmlLogfile()) {
            try {
                new PMD($arguments->pmdCpdXmlLogfile())->processClones($clones);
            } catch (LoggerException $exception) {
                print 'Logger error: ' . $exception->getMessage() . PHP_EOL;

                return 1;
            }
        }

        if ($arguments->jsonLogfile()) {
            try {
                new Json($arguments->jsonLogfile())->processClones($clones);
            } catch (LoggerException $exception) {
                print 'Logger error: ' . $exception->getMessage() . PHP_EOL;

                return 1;
            }
        }

        if ($arguments->sarifLogfile()) {
            try {
                new Sarif($arguments->sarifLogfile())->processClones($clones);
            } catch (LoggerException $exception) {
                print 'Logger error: ' . $exception->getMessage() . PHP_EOL;

                return 1;
            }
        }

        print new ResourceUsageFormatter()->resourceUsage($timer->stop()) . PHP_EOL;

        return count($clones) > 0 ? 1 : 0;
    }

    private function printVersion(): void
    {
        /** @var non-empty-string $path */
        $path = dirname(__DIR__);
        printf('%s %s', 'Copy/Paste Detector', new Version(self::VERSION, $path)->asString());
    }

    /**
     * @throws InvalidStrategyException
     */
    private function pickStrategy(
        string $algorithm,
        StrategyConfiguration $config,
        SuppressionGuard $guard
    ): AbstractStrategy {
        return match ($algorithm) {
            ArgumentsBuilder::ALGORITHM_RABIN_KARP_NAME => new DefaultStrategy($config, $guard),
            ArgumentsBuilder::ALGORITHM_SUFFIX_TREE_NAME => new SuffixTreeStrategy($config, $guard),
            default => throw new InvalidStrategyException('Unsupported algorithm: ' . $algorithm),
        };
    }

    /**
     * Filters out files that match any of the provided exclude paths.
     *
     * This method implements a cross-platform exact segment matching for exclusions.
     * It serves as a reliable patch for the broken --exclude behavior inherited
     * from upstream dependencies (phpunit/php-file-iterator 6.x).
     *
     * @see https://github.com/sebastianbergmann/phpcpd/issues/202
     *
     * @param array<int, string> $files List of file paths to filter
     * @param array<int, string> $exclude List of substrings to exclude (e.g., ['TCA', 'vendor'])
     *
     * @return array<int, string> Filtered list of file paths
     */
    private function filterExcludedFiles(array $files, array $exclude): array
    {
        if (empty($exclude)) {
            return $files;
        }

        // Pre-compile exclude patterns to optimize performance inside the filter loop
        $excludePatterns = [];
        foreach ($exclude as $excludeString) {
            // Normalize user input (in case they provided paths with backslashes)
            $normalizedExclude = str_replace('\\', '/', $excludeString);

            // Remove trailing slashes (e.g. "tests/" becomes "tests") to prevent regex mismatch
            $cleanExclude = rtrim($normalizedExclude, '/');

            // Match exact path segments to prevent false positives (e.g. "Vendor" matching "Vendor2").
            // (?:^|/) - segment must start at the beginning of the string or immediately after a slash
            // (?:/|$) - segment must end with a slash or at the end of the string
            $excludePatterns[] = '#(?:^|/)' . preg_quote($cleanExclude, '#') . '(?:/|$)#';
        }

        return array_filter($files, static function (string $file) use ($excludePatterns) {
            $realPath = realpath($file) ?: $file;

            // Normalize the file path: convert Windows backslashes '\' to Unix forward slashes '/'
            $normalizedPath = str_replace('\\', '/', $realPath);

            foreach ($excludePatterns as $pattern) {
                if (preg_match($pattern, $normalizedPath)) {
                    return false; // Exclude this file
                }
            }

            return true; // Keep this file
        });
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

  --algorithm <name>  Select which algorithm to use ('rabin-karp' (default) or 'suffix-tree')
  --fuzzy             Fuzz variable names
  --min-lines <N>     Minimum number of identical lines (default: 5)
  --min-tokens <N>    Minimum number of identical tokens (default: 70)
  --edit-distance <N> Distance in number of edits between two clones (only for suffix-tree; default: 0)
  --head-equality <N> Minimum equality at start of clone (only for suffix-tree; default 10)
  --verbose           Print results details
  --ignore-no-files   To return a success exit code if no files were found

Options for report generation:

  --log-pmd <file>  Write log in PMD-CPD XML format to <file>
  --log-json <file> Write log in custom JSON format to <file>
  --log-sarif <file> Write log in SARIF 2.1.0 format to <file>

General options:

  --version         Display version
  --help            Display help

EOT;
    }
}
