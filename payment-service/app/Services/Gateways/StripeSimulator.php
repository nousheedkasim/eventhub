<?php

namespace App\Services\Gateways;

class StripeSimulator implements GatewaySimulatorInterface
{
    private float $successRate;

    public function __construct()
    {
        $this->successRate = (float) env('STRIPE_SUCCESS_RATE', 0.90);
    }

    public function charge(float $amount, string $currency): array
    {
        $randomVal = mt_rand(0, 1000) / 1000.0;
        $willSucceed = ($randomVal <= $this->successRate);

        return [
            'success' => $willSucceed,
            'status' => $willSucceed ? 'paid' : 'failed',
            'payment_reference' => 'pay_stripe_' . uniqid(),
        ];
    }
}
