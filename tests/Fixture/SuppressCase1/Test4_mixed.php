<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Fixture\SuppressCase1;

use Systemsdk\PhpCPD\Attributes\SuppressCpd;

class Case4Mixed
{
    #[SuppressCpd]
    public function ignoredMethod(): string
    {
        $data = 'some big logic';
        $data .= ' processing';
        $data .= ' more processing';

        return $data;
    }

    public function detectedMethod(): int
    {
        $calc = 100 * 200;
        $calc /= 50;
        $calc += 10;

        return $calc;
    }
}

class Case4Copy
{
    public function ignoredMethod(): string
    {
        $data = 'some big logic';
        $data .= ' processing';
        $data .= ' more processing';

        return $data;
    }

    public function detectedMethod(): int
    {
        $calc = 100 * 200;
        $calc /= 50;
        $calc += 10;

        return $calc;
    }
}
