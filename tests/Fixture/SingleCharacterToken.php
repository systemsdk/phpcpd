<?php

declare(strict_types=1);

class SingleCharacterToken
{
    public function calculateTotalWithTax(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $price = $item['price'];
            $qty = $item['quantity'];

            // Calculate the sum using the addition operator (+)
            $total = $total + $price + $qty;
        }

        return $total;
    }

    public function calculateTotalWithDiscount(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $price = $item['price'];
            $qty = $item['quantity'];

            // Calculate the product using the multiplication operator (*)
            $total = $total * $price * $qty;
        }

        return $total;
    }
}
