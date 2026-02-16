<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Fixture\SuppressCase2;

use Systemsdk\PhpCPD\Attributes\SuppressCpd;

class Test1_A
{
    #[SuppressCpd]
    public function calculateMathGroup1(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if ($value % 2 === 0) {
                $result[$key] = ($value * 100) / 3.14;
            } else {
                $result[$key] = ($value * 200) / 2.71;
            }
            $result[$key] = round($result[$key], 2);
            $result[$key] += $key;
            $a = 1; $b = 2; $c = 3; $d = 4;
            $result[$key] -= ($a + $b + $c + $d);
        }
        return $result;
    }
}
