<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Cli;

use SebastianBergmann\CliParser\Exception as CliParserException;
use SebastianBergmann\CliParser\Parser as CliParser;
use Systemsdk\PhpCPD\Exceptions\ArgumentsBuilderException;

final class ArgumentsBuilder
{
    public const string OPTION_SUFFIX_NAME = 'suffix';
    public const string OPTION_EXCLUDE_NAME = 'exclude';
    public const string OPTION_LOG_PMD_NAME = 'log-pmd';
    public const string OPTION_FUZZY_NAME = 'fuzzy';
    public const string OPTION_MIN_LINES_NAME = 'min-lines';
    public const string OPTION_MIN_TOKENS_NAME = 'min-tokens';
    public const string OPTION_HEAD_EQUALITY_NAME = 'head-equality';
    public const string OPTION_EDIT_DISTANCE_NAME = 'edit-distance';
    public const string OPTION_VERBOSE_NAME = 'verbose';
    public const string OPTION_HELP_NAME = 'help';
    public const string OPTION_HELP_SHORT_NAME = 'h';
    public const string OPTION_VERSION_NAME = 'version';
    public const string OPTION_VERSION_SHORT_NAME = 'v';
    public const string OPTION_ALGORITHM_NAME = 'algorithm';
    public const string ALGORITHM_RABIN_KARP_NAME = 'rabin-karp';
    public const string ALGORITHM_SUFFIX_TREE_NAME = 'suffix-tree';

    /**
     * @param array<int, string> $argv
     *
     * @throws ArgumentsBuilderException
     */
    public function build(array $argv): Arguments
    {
        try {
            /** @var list<string> $argv */
            $options = (new CliParser())->parse(
                $argv,
                self::OPTION_HELP_SHORT_NAME . self::OPTION_VERSION_SHORT_NAME,
                [
                    self::OPTION_SUFFIX_NAME . '=',
                    self::OPTION_EXCLUDE_NAME . '=',
                    self::OPTION_LOG_PMD_NAME . '=',
                    self::OPTION_FUZZY_NAME,
                    self::OPTION_MIN_LINES_NAME . '=',
                    self::OPTION_MIN_TOKENS_NAME . '=',
                    self::OPTION_HEAD_EQUALITY_NAME . '=',
                    self::OPTION_EDIT_DISTANCE_NAME . '=',
                    self::OPTION_VERBOSE_NAME,
                    self::OPTION_HELP_NAME,
                    self::OPTION_VERSION_NAME,
                    self::OPTION_ALGORITHM_NAME . '=',
                ]
            );
        } catch (CliParserException $e) {
            throw new ArgumentsBuilderException($e->getMessage(), (int)$e->getCode(), $e);
        }

        /** @var array<int, string> $directories */
        $directories = $options[1];

        $suffixes = ['.php'];
        $exclude = [];
        $pmdCpdXmlLogfile = null;
        $fuzzy = false;
        $linesThreshold = 5;
        $tokensThreshold = 70;
        $headEquality = 10;
        $editDistance = 0;
        $verbose = false;
        $help = false;
        $version = false;

        $algorithm = self::ALGORITHM_RABIN_KARP_NAME;

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
            }
        }

        if (empty($directories) && !$help && !$version) {
            throw new ArgumentsBuilderException('No directory specified');
        }

        return new Arguments(
            $directories,
            $suffixes,
            $exclude,
            $pmdCpdXmlLogfile,
            $linesThreshold,
            $tokensThreshold,
            $fuzzy,
            $verbose,
            $help,
            $version,
            $algorithm,
            $editDistance,
            $headEquality
        );
    }
}
