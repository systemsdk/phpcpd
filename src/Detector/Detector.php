<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector;

use Systemsdk\PhpCPD\CodeCloneMap;
use Systemsdk\PhpCPD\Detector\Strategy\AbstractStrategy;
use Systemsdk\PhpCPD\Detector\Strategy\SuffixTreeStrategy;
use Systemsdk\PhpCPD\Detector\Traits\ProgressBarTrait;
use Systemsdk\PhpCPD\Exceptions\ProcessingResultException;

final class Detector
{
    use ProgressBarTrait;

    private const int PROGRESS_BAR_BOUNDARY = 20;
    private const string PROGRESS_BAR_RABIN_KARP_TITLE = 'Loading & Processing';
    private const string PROGRESS_BAR_SUFFIX_TREE_TITLE = 'Loading';

    public function __construct(
        private readonly AbstractStrategy $strategy,
        private readonly bool $useProgressBar = false
    ) {
    }

    /**
     * @param array<int, string> $files
     *
     * @throws ProcessingResultException
     */
    public function copyPasteDetection(array $files): CodeCloneMap
    {
        $result = new CodeCloneMap();
        $totalItems = count($files);
        $processedFiles = 0;
        $boundary = self::PROGRESS_BAR_BOUNDARY;
        foreach ($files as $file) {
            $processedFiles++;

            if (!empty($file)) {
                $this->strategy->processFile($file, $result);
            }

            if ($this->useProgressBar && $this->countProgressBarPercent($processedFiles, $totalItems) >= $boundary) {
                $this->progressBar(
                    $processedFiles,
                    $totalItems,
                    $this->strategy instanceof SuffixTreeStrategy
                        ? self::PROGRESS_BAR_SUFFIX_TREE_TITLE
                        : self::PROGRESS_BAR_RABIN_KARP_TITLE
                );
                $boundary += self::PROGRESS_BAR_BOUNDARY;
            }
        }
        $this->strategy->postProcess($this->useProgressBar);

        return $result;
    }
}
