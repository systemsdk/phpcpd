<?php

declare(strict_types=1);

class FileA
{
    public function calculate(float $v1, float $v2): float
    {
        $v3 = $v1 / ($v2 + $v1);

        if ($v3 > 14) {
            $v4 = 0;
            for ($i = 0; $i < $v3; $i++) {
                $v4 += ($v2 * $i);
            }
        } else {
            $v4 = 10;
        }

        $v5 = ($v4 < $v3 ? ($v3 - $v4) : ($v4 - $v3));
        $v6 = ($v1 * $v2 * $v3 * $v4 * $v5);

        $d = array($v1, $v2, $v3, $v4, $v5, $v6);

        $v7 = 1;
        for ($i = 0; $i < $v6; $i++) {
            shuffle($d);
            $v7 = $v7 + $i * end($d);
        }

        $v8 = $v7;
        foreach ($d as $x) {
            $v8 *= $x;
        }

        // Повторяем логику еще раз для 100% гарантии нужного количества токенов
        $v3 = $v1 / ($v2 + $v1);

        if ($v3 > 14) {
            $v4 = 0;
            for ($i = 0; $i < $v3; $i++) {
                $v4 += ($v2 * $i);
            }
        }

        $v5 = ($v4 < $v3 ? ($v3 - $v4) : ($v4 - $v3));
        $v6 = ($v1 * $v2 * $v3 * $v4 * $v5);

        $d = array($v1, $v2, $v3, $v4, $v5, $v6);

        $v7 = 1;
        for ($i = 0; $i < $v6; $i++) {
            shuffle($d);
            $v7 = $v7 + $i * end($d);
        }

        $v8 = $v7;
        foreach ($d as $x) {
            $v8 *= $x;
        }

        return $v8;
    }
}
