<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PayoutService;

class PayoutServiceTest extends TestCase
{
    private PayoutService $payoutService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payoutService = $this->app->make(PayoutService::class);
    }

    /**
     * Test payout calculations, commission deductions, and decimal rounding.
     */
    public function test_calculate_payout_commission_rounding(): void
    {
        // Gross amount: $123.45, Commission Rate: 10% (0.10)
        // Commission = round(123.45 * 0.10, 2) = round(12.345, 2) = 12.35
        // Net amount = round(123.45 - 12.35, 2) = 111.10
        $result = $this->payoutService->calculatePayout(123.45, 0.10, 50.00);

        $this->assertEquals(123.45, $result['gross_amount']);
        $this->assertEquals(12.35, $result['commission']);
        $this->assertEquals(111.10, $result['amount']);
        $this->assertTrue($result['eligible']);
    }

    /**
     * Test that minimum payout thresholds are correctly enforced.
     */
    public function test_calculate_payout_threshold_enforcement(): void
    {
        // Eligible case: Net amount is equal to or greater than minimum threshold
        $eligibleResult = $this->payoutService->calculatePayout(100.00, 0.10, 90.00);
        $this->assertTrue($eligibleResult['eligible']);

        // Ineligible case: Net amount is less than minimum threshold
        // Gross: $50.00, Commission Rate: 10% (0.10) -> Net: $45.00. Threshold: $50.00
        $ineligibleResult = $this->payoutService->calculatePayout(50.00, 0.10, 50.00);
        $this->assertFalse($ineligibleResult['eligible']);
    }
}
