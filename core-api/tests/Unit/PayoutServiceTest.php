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

    public function test_calculate_payout_commission_rounding(): void
    {
        $result = $this->payoutService->calculatePayout(12345, 0.10, 5000);

        $this->assertEquals(12345, $result['gross_amount']);
        $this->assertEquals(1235, $result['commission']);
        $this->assertEquals(11110, $result['amount']);
        $this->assertTrue($result['eligible']);
    }

    public function test_calculate_payout_threshold_enforcement(): void
    {
        $eligibleResult = $this->payoutService->calculatePayout(10000, 0.10, 9000);
        $this->assertTrue($eligibleResult['eligible']);

        $ineligibleResult = $this->payoutService->calculatePayout(5000, 0.10, 5000);
        $this->assertFalse($ineligibleResult['eligible']);
    }

    public function test_zero_commission_rate(): void
    {
        $result = $this->payoutService->calculatePayout(50000, 0.0, 10000);

        $this->assertEquals(0, $result['commission']);
        $this->assertEquals(50000, $result['amount']);
        $this->assertTrue($result['eligible']);
    }

    public function test_full_commission_rate(): void
    {
        $result = $this->payoutService->calculatePayout(20000, 1.0, 5000);

        $this->assertEquals(20000, $result['commission']);
        $this->assertEquals(0, $result['amount']);
        $this->assertFalse($result['eligible']);
    }

    public function test_zero_gross_amount(): void
    {
        $result = $this->payoutService->calculatePayout(0, 0.10, 5000);

        $this->assertEquals(0, $result['gross_amount']);
        $this->assertEquals(0, $result['commission']);
        $this->assertEquals(0, $result['amount']);
        $this->assertFalse($result['eligible']);
    }

    public function test_zero_threshold_always_eligible(): void
    {
        $result = $this->payoutService->calculatePayout(1000, 0.10, 0);

        $this->assertEquals(900, $result['amount']);
        $this->assertTrue($result['eligible']);
    }

    public function test_commission_rounding_accuracy(): void
    {
        $testCases = [
            ['gross' => 3333, 'rate' => 0.15, 'expected_commission' => 500, 'expected_net' => 2833],
            ['gross' => 9999, 'rate' => 0.15, 'expected_commission' => 1500, 'expected_net' => 8499],
            ['gross' => 101, 'rate' => 0.30, 'expected_commission' => 30, 'expected_net' => 71],
        ];

        foreach ($testCases as $case) {
            $result = $this->payoutService->calculatePayout($case['gross'], $case['rate'], 0);
            $this->assertEquals($case['expected_commission'], $result['commission'], "Commission mismatch for gross {$case['gross']}");
            $this->assertEquals($case['expected_net'], $result['amount'], "Net mismatch for gross {$case['gross']}");
        }
    }

    public function test_threshold_boundary_exact_match(): void
    {
        $result = $this->payoutService->calculatePayout(10000, 0.10, 9000);
        $this->assertTrue($result['eligible']);

        $result = $this->payoutService->calculatePayout(9999, 0.10, 9000);
        $this->assertFalse($result['eligible']);

        $result = $this->payoutService->calculatePayout(10100, 0.10, 9100);
        $this->assertFalse($result['eligible']);
    }
}
