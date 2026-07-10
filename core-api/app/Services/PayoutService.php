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

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    /**
     * Calculate commission deduction and enforce minimum payout threshold
     */
    public function calculatePayout(float $grossAmount, float $commissionRate, float $minThreshold): array
    {
        $commission = round($grossAmount * $commissionRate, 2);
        $netAmount = round($grossAmount - $commission, 2);
        $eligible = ($netAmount >= $minThreshold);

        return [
            'gross_amount' => $grossAmount,
            'commission' => $commission,
            'amount' => $netAmount,
            'eligible' => $eligible,
            'minimum_threshold' => $minThreshold,
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
