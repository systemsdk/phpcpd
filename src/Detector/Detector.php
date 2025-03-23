<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector;

use Systemsdk\PhpCPD\CodeCloneMap;
use Systemsdk\PhpCPD\Detector\Strategy\AbstractStrategy;
use Systemsdk\PhpCPD\Exceptions\ProcessingResultException;

use function sprintf;

final class Detector
{
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
        foreach ($files as $file) {
            $processedFiles++;

            if (empty($file)) {
                $this->progressBar($processedFiles, $totalItems);

                continue;
            }

            $this->strategy->processFile($file, $result);
            $this->progressBar($processedFiles, $totalItems);
        }
        $this->strategy->postProcess();

        return $result;
    }

    private function progressBar(int $done, int $total, string $title = '', int $width = 30): void
    {
        if ($this->useProgressBar === false) {
            return;
        }

        $perc = (int)floor(($done * 100) / $total);
        $bar = (int)floor(($width * $perc) / 100);

        print sprintf(
            " %s/%s [%s>%s] %s%% %s\r",
            $done,
            $total,
            str_repeat('=', $bar),
            str_repeat(' ', $width - $bar),
            $perc,
            $title
        );

        if ($done >= $total) {
            print PHP_EOL;
        }
    }
}
