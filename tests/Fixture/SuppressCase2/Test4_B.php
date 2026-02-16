<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Fixture\SuppressCase2;

class Test4_B
{
    public function smallMethod(int $a, int $b): int
    {
        return $a + $b;
    }

    public function massiveMethodToEnsureLargeClone(array $items): int
    {
        $sum = 0;
        foreach ($items as $item) {
            if ($item > 100) {
                $sum += $item * 2;
            } elseif ($item > 50) {
                $sum += $item * 1.5;
            } else {
                $sum += $item;
            }
            $a = 1; $b = 2; $c = 3; $d = 4;
            $sum -= ($a + $b + $c + $d);
            $sum = (int)round($sum);
        }
        return $sum;
    }
}
