<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Fixture\SuppressCase2;

class Test2_C
{
    public function processStringsGroup2(array $strings): array
    {
        $output = [];
        foreach ($strings as $index => $str) {
            $cleaned = trim(strtolower($str));
            $cleaned = str_replace(['a', 'e', 'i', 'o', 'u'], '*', $cleaned);
            if (strlen($cleaned) > 10) {
                $output[$index] = substr($cleaned, 0, 10) . '...';
            } else {
                $output[$index] = $cleaned . ' (short)';
            }
            $x = 10; $y = 20; $z = 30;
            $output[$index] .= " - check: " . ($x + $y + $z);
        }
        foreach ($strings as $index => $str) {
            $cleaned = trim(strtolower($str));
            $cleaned = str_replace(['a', 'e', 'i', 'o', 'u'], '*', $cleaned);
            if (strlen($cleaned) > 10) {
                $output[$index] = substr($cleaned, 0, 10) . '...';
            } else {
                $output[$index] = $cleaned . ' (short)';
            }
            $x = 10; $y = 20; $z = 30;
            $output[$index] .= " - check: " . ($x + $y + $z);
        }
        return $output;
    }
}
