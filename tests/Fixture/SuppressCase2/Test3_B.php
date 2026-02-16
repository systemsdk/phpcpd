<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Tests\Fixture\SuppressCase2;

use Systemsdk\PhpCPD\Attributes\SuppressCpd;

class Test3_B
{
    #[SuppressCpd]
    public function generateReportGroup3(array $data): string
    {
        $report = "START REPORT\n";
        $report .= "============\n";
        $total = 0;
        foreach ($data as $item) {
            $val1 = $item['price'] ?? 0;
            $val2 = $item['tax'] ?? 0;
            $total += ($val1 + $val2);
            $report .= sprintf("Item: %s, Cost: %d\n", $item['name'] ?? 'Unknown', $val1 + $val2);
            $q = 1; $w = 2; $e = 3; $r = 4;
            $total += ($q * $w * $e * $r);
        }
        $report .= "============\n";
        $report .= "TOTAL: " . $total;
        $report = "START REPORT\n";
        $report .= "============\n";
        $total = 0;
        foreach ($data as $item) {
            $val1 = $item['price'] ?? 0;
            $val2 = $item['tax'] ?? 0;
            $total += ($val1 + $val2);
            $report .= sprintf("Item: %s, Cost: %d\n", $item['name'] ?? 'Unknown', $val1 + $val2);
            $q = 1; $w = 2; $e = 3; $r = 4;
            $total += ($q * $w * $e * $r);
        }
        $report .= "============\n";
        $report .= "TOTAL: " . $total;
        return $report;
    }
}
