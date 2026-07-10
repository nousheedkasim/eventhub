<?php

namespace App\Services\Gateways;

interface GatewaySimulatorInterface
{
    public function charge(float $amount, string $currency): array;
}
