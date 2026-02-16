<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Fixture\SuppressCase1;

use Systemsdk\PhpCPD\Attributes\SuppressCpd;

class Case1Original
{
    #[SuppressCpd]
    public function algorithm(): int
    {
        $a = 1;
        $b = 2;
        $c = $a + $b;
        $d = $c * $a;

        return $d;
    }
}

class Case1Copy
{
    public function algorithm(): int
    {
        $a = 1;
        $b = 2;
        $c = $a + $b;
        $d = $c * $a;

        return $d;
    }
}
