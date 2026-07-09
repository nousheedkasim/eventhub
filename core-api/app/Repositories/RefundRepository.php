<?php

namespace App\Repositories;

use App\Models\Refund;
use App\Repositories\Contracts\RefundRepositoryInterface;

class RefundRepository implements RefundRepositoryInterface
{
    public function all()
    {
        return Refund::query()->paginate(15);
    }

    public function find($id)
    {
        return Refund::findOrFail($id);
    }

    public function create(array $data)
    {
        return Refund::create($data);
    }

    public function update($id, array $data)
    {
        $refund = Refund::findOrFail($id);
        $refund->update($data);
        return $refund;
    }

    public function delete($id)
    {
        $refund = Refund::findOrFail($id);
        return $refund->delete();
    }
}

