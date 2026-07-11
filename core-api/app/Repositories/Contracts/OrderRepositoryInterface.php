<?php

namespace App\Repositories\Contracts;

interface OrderRepositoryInterface
{
    public function all();

    public function getByVendor($vendorId);

    public function find($id);

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);
}

