<?php

namespace App\Services;

use App\Repositories\TicketTypeRepository;

class TicketTypeService
{
    public function __construct(
        private TicketTypeRepository $repository
    ) {}

    public function getAll()
    {
        return $this->repository->all();
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
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

