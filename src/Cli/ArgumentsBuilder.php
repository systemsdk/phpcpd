<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Cli;

use SebastianBergmann\CliParser\Exception as CliParserException;
use SebastianBergmann\CliParser\Parser as CliParser;
use Systemsdk\PhpCPD\Config\Config;
use Systemsdk\PhpCPD\Exceptions\ArgumentsBuilderException;
use Systemsdk\PhpCPD\Exceptions\InvalidConfigurationException;

use function getcwd;
use function is_file;

final class ArgumentsBuilder
{
    public const string OPTION_SUFFIX_NAME = 'suffix';
    public const string OPTION_EXCLUDE_NAME = 'exclude';
    public const string OPTION_LOG_PMD_NAME = 'log-pmd';
    public const string OPTION_LOG_JSON_NAME = 'log-json';
    public const string OPTION_LOG_SARIF_NAME = 'log-sarif';
    public const string OPTION_FUZZY_NAME = 'fuzzy';
    public const string OPTION_MIN_LINES_NAME = 'min-lines';
    public const string OPTION_MIN_TOKENS_NAME = 'min-tokens';
    public const string OPTION_HEAD_EQUALITY_NAME = 'head-equality';
    public const string OPTION_EDIT_DISTANCE_NAME = 'edit-distance';
    public const string OPTION_VERBOSE_NAME = 'verbose';
    public const string OPTION_QUIET_NAME = 'quiet';
    public const string OPTION_HELP_NAME = 'help';
    public const string OPTION_HELP_SHORT_NAME = 'h';
    public const string OPTION_VERSION_NAME = 'version';
    public const string OPTION_VERSION_SHORT_NAME = 'v';
    public const string OPTION_ALGORITHM_NAME = 'algorithm';
    public const string ALGORITHM_RABIN_KARP_NAME = 'rabin-karp';
    public const string ALGORITHM_SUFFIX_TREE_NAME = 'suffix-tree';
    public const string OPTION_IGNORE_NO_FILES = 'ignore-no-files';
    public const string OPTION_MAX_PERCENTAGE_NAME = 'max-percentage';
    public const string OPTION_IGNORE_VIOLATIONS_ON_EXIT_NAME = 'ignore-violations-on-exit';

    /**
     * @param array<int, string> $argv
     *
     * @throws ArgumentsBuilderException
     */
    public function build(array $argv): Arguments
    {
        try {
            /** @var list<string> $argv */
            $options = new CliParser()->parse(
                $argv,
                self::OPTION_HELP_SHORT_NAME . self::OPTION_VERSION_SHORT_NAME,
                [
                    self::OPTION_SUFFIX_NAME . '=',
                    self::OPTION_EXCLUDE_NAME . '=',
                    self::OPTION_LOG_PMD_NAME . '=',
                    self::OPTION_LOG_JSON_NAME . '=',
                    self::OPTION_LOG_SARIF_NAME . '=',
                    self::OPTION_FUZZY_NAME,
                    self::OPTION_MIN_LINES_NAME . '=',
                    self::OPTION_MIN_TOKENS_NAME . '=',
                    self::OPTION_HEAD_EQUALITY_NAME . '=',
                    self::OPTION_EDIT_DISTANCE_NAME . '=',
                    self::OPTION_VERBOSE_NAME,
                    self::OPTION_QUIET_NAME,
                    self::OPTION_HELP_NAME,
                    self::OPTION_VERSION_NAME,
                    self::OPTION_ALGORITHM_NAME . '=',
                    self::OPTION_IGNORE_NO_FILES,
                    self::OPTION_MAX_PERCENTAGE_NAME . '=',
                    self::OPTION_IGNORE_VIOLATIONS_ON_EXIT_NAME,
                ]
            );
        } catch (CliParserException $e) {
            throw new ArgumentsBuilderException($e->getMessage(), (int)$e->getCode(), $e);
        }

        // 1. Load config file if it exists
        $config = $this->loadConfiguration();

        // 2. Initialize variables from config
        $suffixes = $config->getSuffixes();
        $exclude = $config->getExclude();
        $pmdCpdXmlLogfile = $config->getPmdCpdXmlLogfile();
        $jsonLogfile = $config->getJsonLogfile();
        $sarifLogfile = $config->getSarifLogfile();
        $fuzzy = $config->isFuzzy();
        $linesThreshold = $config->getMinLines();
        $tokensThreshold = $config->getMinTokens();
        $headEquality = $config->getHeadEquality();
        $editDistance = $config->getEditDistance();
        $verbose = $config->isVerbose();
        $quiet = $config->isQuiet();
        $ignoreNoFiles = $config->isIgnoreNoFiles();
        $maxPercentage = $config->getMaxPercentage();
        $ignoreViolationsOnExit = $config->isIgnoreViolationsOnExit();
        $algorithm = $config->getAlgorithm();

        $help = false;
        $version = false;

        // 3. Parse CLI arguments (CLI overrides config)
        foreach ($options[0] as $option) {
            switch ($option[0]) {
                case '--' . self::OPTION_SUFFIX_NAME:
                    $suffixes[] = (string)$option[1];

                    break;
                case '--' . self::OPTION_EXCLUDE_NAME:
                    $exclude[] = (string)$option[1];

                    break;
                case '--' . self::OPTION_LOG_PMD_NAME:
                    $pmdCpdXmlLogfile = (string)$option[1];

                    break;
                case '--' . self::OPTION_LOG_JSON_NAME:
                    $jsonLogfile = (string)$option[1];

                    break;
                case '--' . self::OPTION_LOG_SARIF_NAME:
                    $sarifLogfile = (string)$option[1];

                    break;
                case '--' . self::OPTION_FUZZY_NAME:
                    $fuzzy = true;

                    break;
                case '--' . self::OPTION_MIN_LINES_NAME:
                    $linesThreshold = (int)$option[1];

                    break;
                case '--' . self::OPTION_MIN_TOKENS_NAME:
                    $tokensThreshold = (int)$option[1];

                    break;
                case '--' . self::OPTION_HEAD_EQUALITY_NAME:
                    $headEquality = (int)$option[1];

                    break;
                case '--' . self::OPTION_EDIT_DISTANCE_NAME:
                    $editDistance = (int)$option[1];

                    break;
                case '--' . self::OPTION_VERBOSE_NAME:
                    $verbose = true;

                    break;
                case '--' . self::OPTION_QUIET_NAME:
                    $quiet = true;

                    break;
                case self::OPTION_HELP_SHORT_NAME:
                case '--' . self::OPTION_HELP_NAME:
                    $help = true;

                    break;
                case self::OPTION_VERSION_SHORT_NAME:
                case '--' . self::OPTION_VERSION_NAME:
                    $version = true;

                    break;
                case '--' . self::OPTION_ALGORITHM_NAME:
                    $algorithm = (string)$option[1];

                    break;
                case '--' . self::OPTION_IGNORE_NO_FILES:
                    $ignoreNoFiles = true;

                    break;
                case '--' . self::OPTION_MAX_PERCENTAGE_NAME:
                    $maxPercentage = (float)$option[1];

                    break;
                case '--' . self::OPTION_IGNORE_VIOLATIONS_ON_EXIT_NAME:
                    $ignoreViolationsOnExit = true;

                    break;
            }
        }

        /** @var array<int, string> $directories */
        $directories = !empty($options[1]) ? $options[1] : $config->getDirectories();

        if (empty($directories) && !$help && !$version) {
            throw new ArgumentsBuilderException('No directory specified');
        }

        return new Arguments(
            $directories,
            $suffixes,
            $exclude,
            $pmdCpdXmlLogfile,
            $jsonLogfile,
            $sarifLogfile,
            $linesThreshold,
            $tokensThreshold,
            $fuzzy,
            $verbose,
            $help,
            $version,
            $algorithm,
            $editDistance,
            $headEquality,
            $ignoreNoFiles,
            $quiet,
            $maxPercentage,
            $ignoreViolationsOnExit
        );
    }

    /**
     * @throws InvalidConfigurationException
     */
    private function loadConfiguration(): Config
    {
        $configPath = getcwd() . DIRECTORY_SEPARATOR . 'phpcpd.config.php';

        if (!is_file($configPath)) {
            return Config::make();
        }

        $config = require $configPath;

        if (!$config instanceof Config) {
            throw new InvalidConfigurationException(
                'The phpcpd.config.php file must return an instance of ' . Config::class
            );
        }

        return $config;
    }
}
