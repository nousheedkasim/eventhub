<?php

namespace App\Repositories;

use App\Models\TicketType;
use App\Repositories\Contracts\TicketTypeRepositoryInterface;

class TicketTypeRepository implements TicketTypeRepositoryInterface
{
    public function all()
    {
        return TicketType::query()->paginate(15);
    }

    public function find($id)
    {
        return TicketType::findOrFail($id);
    }

    public function create(array $data)
    {
        return TicketType::create($data);
    }

    public function update($id, array $data)
    {
        $ticketType = TicketType::findOrFail($id);
        $ticketType->update($data);
        return $ticketType;
    }

    public function delete($id)
    {
        $ticketType = TicketType::findOrFail($id);
        return $ticketType->delete();
    }
}

