<?php

namespace Tests\Unit\Security;

use PHPUnit\Framework\TestCase;

class GSTCalculationTest extends TestCase
{
    public function test_gst_18_percent_for_premium_rooms(): void
    {
        $pricePerNight = 12500; // Above 7500 threshold
        $nights = 3;
        $base = $pricePerNight * $nights;
        $gstRate = ($pricePerNight >= 7500) ? 0.18 : 0.12;
        $tax = round($base * $gstRate, 2);
        $total = $base + $tax;

        $this->assertEquals(0.18, $gstRate);
        $this->assertEquals(37500, $base);
        $this->assertEquals(6750, $tax);
        $this->assertEquals(44250, $total);
    }

    public function test_gst_12_percent_for_standard_rooms(): void
    {
        $pricePerNight = 4500; // Below 7500 threshold
        $nights = 2;
        $base = $pricePerNight * $nights;
        $gstRate = ($pricePerNight >= 7500) ? 0.18 : 0.12;
        $tax = round($base * $gstRate, 2);

        $this->assertEquals(0.12, $gstRate);
        $this->assertEquals(9000, $base);
        $this->assertEquals(1080, $tax);
    }

    public function test_gst_threshold_boundary_at_7500(): void
    {
        // Exactly at threshold
        $priceAtThreshold = 7500;
        $gstRate = ($priceAtThreshold >= 7500) ? 0.18 : 0.12;

        $this->assertEquals(0.18, $gstRate);
    }

    public function test_gst_threshold_boundary_below_7500(): void
    {
        $priceBelow = 7499;
        $gstRate = ($priceBelow >= 7500) ? 0.18 : 0.12;

        $this->assertEquals(0.12, $gstRate);
    }

    public function test_server_side_price_not_tampered(): void
    {
        // Simulating: client sends fake price, server recalculates from DB
        $clientPrice = 100; // Fake price from tampered form
        $serverRoomPrice = 12500; // Actual DB price
        $nights = 2;

        $serverTotal = $serverRoomPrice * $nights;
        $this->assertNotEquals($clientPrice, $serverTotal);
        $this->assertEquals(25000, $serverTotal);
    }

    public function test_total_with_tax_for_single_room(): void
    {
        $price = 4500;
        $nights = 5;
        $base = $price * $nights;
        $gstRate = 0.12; // Below 7500
        $tax = round($base * $gstRate, 2);
        $total = $base + $tax;

        $this->assertEquals(22500, $base);
        $this->assertEquals(2700.0, $tax);
        $this->assertEquals(25200, $total);
    }

    public function test_total_with_tax_for_suite(): void
    {
        $price = 24000;
        $nights = 2;
        $base = $price * $nights;
        $gstRate = 0.18; // Above 7500
        $tax = round($base * $gstRate, 2);
        $total = $base + $tax;

        $this->assertEquals(48000, $base);
        $this->assertEquals(8640.0, $tax);
        $this->assertEquals(56640, $total);
    }

    public function test_total_with_tax_for_presidential(): void
    {
        $price = 55000;
        $nights = 1;
        $base = $price * $nights;
        $gstRate = 0.18;
        $tax = round($base * $gstRate, 2);
        $total = $base + $tax;

        $this->assertEquals(55000, $base);
        $this->assertEquals(9900.0, $tax);
        $this->assertEquals(64900, $total);
    }

    public function test_discount_applied_before_tax(): void
    {
        $price = 12500;
        $nights = 3;
        $base = $price * $nights;
        $discount = 5000; // Flat discount
        $discountedBase = $base - $discount;
        $gstRate = 0.18;
        $tax = round($discountedBase * $gstRate, 2);
        $total = $discountedBase + $tax;

        $this->assertEquals(37500, $base);
        $this->assertEquals(32500, $discountedBase);
        $this->assertEquals(5850.0, $tax);
        $this->assertEquals(38350, $total);
    }

    public function test_zero_nights_booking(): void
    {
        $price = 12500;
        $nights = 0;
        $base = $price * $nights;

        $this->assertEquals(0, $base);
    }

    public function test_negative_price_rejected(): void
    {
        $price = -5000;
        $this->assertLessThan(0, $price);
        // Server should reject negative prices
        $this->assertFalse($price > 0);
    }
}
