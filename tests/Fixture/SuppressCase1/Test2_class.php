<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Fixture\SuppressCase1;

use Systemsdk\PhpCPD\Attributes\SuppressCpd;

#[SuppressCpd]
class Case2IgnoredClass
{
    public function methodA(): void
    {
        echo "line 1";
        echo "line 2";
        echo "line 3";
        echo "line 4";
        echo "line 5";
    }

    public function methodB(): void
    {
        echo "line 1";
        echo "line 2";
        echo "line 3";
        echo "line 4";
        echo "line 5";
    }
}

class Case2LeakyClass
{
    public function methodA(): void
    {
        echo "line 1";
        echo "line 2";
        echo "line 3";
        echo "line 4";
        echo "line 5";
    }

    public function methodB(): void
    {
        echo "line 1";
        echo "line 2";
        echo "line 3";
        echo "line 4";
        echo "line 5";
    }
}
