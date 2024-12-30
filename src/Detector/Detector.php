<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector;

use Systemsdk\PhpCPD\CodeCloneMap;
use Systemsdk\PhpCPD\Detector\Strategy\AbstractStrategy;
use Systemsdk\PhpCPD\Exceptions\ProcessingResultException;

final class Detector
{
    public function __construct(
        private readonly AbstractStrategy $strategy
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
        foreach ($files as $file) {
            if (empty($file)) {
                continue;
            }

            $this->strategy->processFile($file, $result);
        }
        $this->strategy->postProcess();

        return $result;
    }
}
