<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Fixture\SuppressCase1;

use Systemsdk\PhpCPD\Attributes\SuppressCpd;

class Case5Hooks
{
    public string $name {
        #[SuppressCpd]
        set {
            $value = strtoupper($value);
            $value = trim($value);
            $value = ucfirst($value);
            $this->name = $value;
        }
        get {
            return $this->name;
        }
    }
}

class Case5Copy
{
    public function manualSetter($value): void
    {
        $value = strtoupper($value);
        $value = trim($value);
        $value = ucfirst($value);
        $this->name = $value;
    }
}
