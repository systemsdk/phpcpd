<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Log;

use Systemsdk\PhpCPD\CodeCloneMap;

use function count;
use function printf;

use const PHP_EOL;

final class Text
{
    public function printResult(CodeCloneMap $clones, bool $verbose): void
    {
        $countClones = count($clones);

        if ($countClones > 0) {
            printf(
                'Found %d code clones with %d duplicated lines in %d files:' . PHP_EOL . PHP_EOL,
                $countClones,
                $clones->numberOfDuplicatedLines(),
                $clones->numberOfFilesWithClones()
            );
        }

        foreach ($clones as $clone) {
            $firstOccurrence = true;

            foreach ($clone->files() as $file) {
                printf(
                    '  %s%s:%d-%d%s' . PHP_EOL,
                    $firstOccurrence ? '- ' : '  ',
                    $file->name(),
                    $file->startLine(),
                    $file->startLine() + $clone->numberOfLines(),
                    $firstOccurrence ? ' (' . $clone->numberOfLines() . ' lines)' : ''
                );

                $firstOccurrence = false;
            }

            if ($verbose) {
                print PHP_EOL . $clone->lines('    ');
            }

            print PHP_EOL;
        }

        if ($clones->isEmpty()) {
            print 'No code clones found.' . PHP_EOL . PHP_EOL;

            return;
        }

        printf(
            '%s duplicated lines out of %d total lines of code.' . PHP_EOL .
            'Average code clone size is %d lines, the largest code clone has %d lines' . PHP_EOL . PHP_EOL,
            $clones->percentage(),
            $clones->numberOfLines(),
            $clones->averageSize(),
            $clones->largestSize()
        );
    }
}
