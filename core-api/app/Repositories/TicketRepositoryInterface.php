<?php

namespace App\Repositories;

use App\Models\TicketType;

interface TicketRepositoryInterface
{
    public function findWithLock(int $ticketTypeId): ?TicketType;
    public function updateInventory(TicketType $ticketType, int $quantity): bool;
}