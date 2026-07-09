<?php

namespace App\Repositories;

use App\Models\PayoutBatch;
use App\Repositories\Contracts\PayoutBatchRepositoryInterface;

class PayoutBatchRepository implements PayoutBatchRepositoryInterface
{
    public function all()
    {
        return PayoutBatch::query()->paginate(15);
    }

    public function find($id)
    {
        return PayoutBatch::findOrFail($id);
    }

    public function create(array $data)
    {
        return PayoutBatch::create($data);
    }

    public function update($id, array $data)
    {
        $batch = PayoutBatch::findOrFail($id);
        $batch->update($data);
        return $batch;
    }

    public function delete($id)
    {
        $batch = PayoutBatch::findOrFail($id);
        return $batch->delete();
    }
}

