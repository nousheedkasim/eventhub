<?php

namespace App\Repositories;

use App\Models\TicketType;

class TicketRepository implements TicketRepositoryInterface
{
    public function findWithLock(int $ticketTypeId): ?TicketType
    {
        // Enforces an InnoDB row-level lock for safe transactional operations
        return TicketType::where('id', $ticketTypeId)->lockForUpdate()->first();
    }

    public function updateInventory(TicketType $ticketType, int $quantity): bool
    {
        if ($ticketType->remaining_inventory < $quantity) {
            return false;
        }

        $ticketType->decrement('remaining_inventory', $quantity);
        return true;
    }
}