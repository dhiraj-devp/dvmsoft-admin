<?php

namespace App\Services;

class QuotationCalculator
{
    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{items: list<array<string, mixed>>, subtotal: float, discount_amount: float, tax_amount: float, total: float}
     */
    public function calculate(array $items, float $headerDiscountPercent = 0): array
    {
        $normalized = [];
        $subtotal = 0.0;
        $lineDiscountTotal = 0.0;
        $taxAmount = 0.0;

        foreach (array_values($items) as $index => $item) {
            $quantity = max(0, (float) ($item['quantity'] ?? 0));
            $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));
            $discountPercent = min(100, max(0, (float) ($item['discount_percent'] ?? 0)));
            $taxPercent = min(100, max(0, (float) ($item['tax_percent'] ?? 0)));

            $base = round($quantity * $unitPrice, 2);
            $discount = round($base * ($discountPercent / 100), 2);
            $taxable = round($base - $discount, 2);
            $tax = round($taxable * ($taxPercent / 100), 2);
            $lineTotal = round($taxable + $tax, 2);

            $subtotal += $base;
            $lineDiscountTotal += $discount;
            $taxAmount += $tax;

            $normalized[] = [
                'description' => trim((string) ($item['description'] ?? '')),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'tax_percent' => $taxPercent,
                'line_total' => $lineTotal,
                'sort_order' => $index,
            ];
        }

        $headerDiscountPercent = min(100, max(0, $headerDiscountPercent));
        $netAfterLineDiscount = round($subtotal - $lineDiscountTotal, 2);
        $headerDiscount = round($netAfterLineDiscount * ($headerDiscountPercent / 100), 2);
        $discountAmount = round($lineDiscountTotal + $headerDiscount, 2);
        $total = round($subtotal - $discountAmount + $taxAmount, 2);

        return [
            'items' => $normalized,
            'subtotal' => round($subtotal, 2),
            'discount_amount' => $discountAmount,
            'tax_amount' => round($taxAmount, 2),
            'total' => max(0, $total),
        ];
    }
}
