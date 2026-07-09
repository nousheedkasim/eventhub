<?php

namespace App\Repositories;

use App\Models\Dispute;
use App\Repositories\Contracts\DisputeRepositoryInterface;

class DisputeRepository implements DisputeRepositoryInterface
{
    public function all()
    {
        return Dispute::query()->paginate(15);
    }

    public function find($id)
    {
        return Dispute::findOrFail($id);
    }

    public function create(array $data)
    {
        return Dispute::create($data);
    }

    public function update($id, array $data)
    {
        $dispute = Dispute::findOrFail($id);
        $dispute->update($data);
        return $dispute;
    }

    public function delete($id)
    {
        $dispute = Dispute::findOrFail($id);
        return $dispute->delete();
    }
}

