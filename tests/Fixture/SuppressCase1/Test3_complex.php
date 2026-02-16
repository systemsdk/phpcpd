<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Fixture\SuppressCase1;

use Systemsdk\PhpCPD\Attributes\SuppressCpd;

class Case3Complex
{
    #[Route('/api', options: ['id' => '\d+']), SuppressCpd, Author('Me')]
    public function complexAttribute(): array
    {
        $x = [1, 2, 3];
        $y = array_map(fn($z) => $z * 2, $x);

        return $y;
    }

    #[\Systemsdk\PhpCPD\Attributes\SuppressCpd]
    public function fqcnAttribute(): array
    {
        $x = [1, 2, 3];
        $y = array_map(fn($z) => $z * 2, $x);

        return $y;
    }
}

class Case3Copy
{
    public function copyOfComplex(): array
    {
        $x = [1, 2, 3];
        $y = array_map(fn($z) => $z * 2, $x);

        return $y;
    }
}
