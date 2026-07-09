<?php

namespace App\Repositories;

use App\Models\Payout;
use App\Repositories\Contracts\PayoutRepositoryInterface;

class PayoutRepository implements PayoutRepositoryInterface
{
    public function all()
    {
        return Payout::query()->paginate(15);
    }

    public function find($id)
    {
        return Payout::findOrFail($id);
    }

    public function create(array $data)
    {
        return Payout::create($data);
    }

    public function update($id, array $data)
    {
        $payout = Payout::findOrFail($id);
        $payout->update($data);
        return $payout;
    }

    public function delete($id)
    {
        $payout = Payout::findOrFail($id);
        return $payout->delete();
    }
}

