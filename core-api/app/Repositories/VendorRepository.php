<?php

namespace App\Repositories;

use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;

class VendorRepository implements VendorRepositoryInterface
{
    public function all()
    {
        return Vendor::query()->paginate(15);
    }

    public function create(array $data)
    {
        return Vendor::create($data);
    }

    public function update($id, array $data)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->update($data);
        return $vendor;
    }

    public function delete($id)
    {
        $vendor = Vendor::findOrFail($id);
        return $vendor->delete();
    }
}

