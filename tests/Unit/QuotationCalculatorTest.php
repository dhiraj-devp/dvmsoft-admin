<?php

namespace Tests\Unit;

use App\Services\QuotationCalculator;
use Tests\TestCase;

class QuotationCalculatorTest extends TestCase
{
    public function test_it_calculates_line_discounts_tax_and_header_discount(): void
    {
        $result = app(QuotationCalculator::class)->calculate([
            [
                'description' => 'Discovery',
                'quantity' => 2,
                'unit_price' => 1000,
                'discount_percent' => 10,
                'tax_percent' => 18,
            ],
        ], 5);

        $this->assertSame(2000.0, $result['subtotal']);
        $this->assertSame(290.0, $result['discount_amount']);
        $this->assertSame(324.0, $result['tax_amount']);
        $this->assertSame(2034.0, $result['total']);
        $this->assertSame(2124.0, $result['items'][0]['line_total']);
    }

    public function test_it_sums_multiple_line_items(): void
    {
        $result = app(QuotationCalculator::class)->calculate([
            [
                'description' => 'Design',
                'quantity' => 1,
                'unit_price' => 5000,
                'discount_percent' => 0,
                'tax_percent' => 18,
            ],
            [
                'description' => 'Development',
                'quantity' => 10,
                'unit_price' => 2000,
                'discount_percent' => 0,
                'tax_percent' => 18,
            ],
        ]);

        $this->assertSame(25000.0, $result['subtotal']);
        $this->assertSame(0.0, $result['discount_amount']);
        $this->assertSame(4500.0, $result['tax_amount']);
        $this->assertSame(29500.0, $result['total']);
    }
}
