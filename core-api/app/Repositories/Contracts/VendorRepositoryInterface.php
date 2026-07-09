<?php

namespace App\Repositories\Contracts;

interface VendorRepositoryInterface
{
    public function all();

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);
}

