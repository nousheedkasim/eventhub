<?php

namespace App\Services;

use App\Repositories\PayoutRepository;
use App\Models\Payout;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    public function __construct(
        private PayoutRepository $repository
    ) {}

    public function getAll()
    {
        return $this->repository->all();
    }

    public function getByVendor($vendorId)
    {
        return $this->repository->getByVendor($vendorId);
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    /**
     * Calculate commission deduction and enforce minimum payout threshold
     * All monetary values in cents (integers)
     */
    public function calculatePayout(int $grossAmountCents, float $commissionRate, int $minThresholdCents): array
    {
        $commission = (int) round($grossAmountCents * $commissionRate);
        $netAmount = $grossAmountCents - $commission;
        $eligible = ($netAmount >= $minThresholdCents);

        return [
            'gross_amount' => $grossAmountCents,
            'commission' => $commission,
            'amount' => $netAmount,
            'eligible' => $eligible,
            'minimum_threshold' => $minThresholdCents,
        ];
    }

    public function find($id)
    {
        return $this->repository->find($id);
    }

    public function update($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->repository->delete($id);
    }
}
