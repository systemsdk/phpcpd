<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Traits;

use function sprintf;

trait ProgressBarTrait
{
    private function progressBar(int $done, int $total, string $title = '', int $width = 30): void
    {
        $perc = $this->countProgressBarPercent($done, $total);
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

    private function countProgressBarPercent(int $done, int $total): int
    {
        return (int)floor(($done * 100) / $total);
    }
}
