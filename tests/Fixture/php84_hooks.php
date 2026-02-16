<?php

declare(strict_types=1);

class User
{
    public string $fullName {
        get {
            $a = 1;
            $b = 2;
            return $this->first . ' ' . $this->last;
        }
        set {
            [$this->first, $this->last] = explode(' ', $value, 2);
        }
    }

    public function __construct(private string $first, private string $last)
    {
    }
}

class UserCopy
{
    public string $fullName {
        get {
            $a = 1;
            $b = 2;
            return $this->first . ' ' . $this->last;
        }
        set {
            [$this->first, $this->last] = explode(' ', $value, 2);
        }
    }

    public function __construct(private string $first, private string $last)
    {
    }
}
