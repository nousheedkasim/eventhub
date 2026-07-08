<?php

namespace App\Services;

use App\Repositories\TicketRepositoryInterface;
use App\Models\TicketReservation;
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryService
{
    protected $ticketRepository;

    public function __construct(TicketRepositoryInterface $ticketRepository)
    {
        $this->ticketRepository = $ticketRepository;
    }

    /**
     * Attempts to reserve tickets safely under high-concurrency conditions.
     */
    public function reserveTickets(int $userId, int $ticketTypeId, int $quantity, string $sessionToken): array
    {
        return DB::transaction(function () use ($userId, $ticketTypeId, $quantity, $sessionToken) {
            // 1. Fetch the ticket type row and lock it down exclusively in InnoDB
            $ticketType = $this->ticketRepository->findWithLock($ticketTypeId);

            if (!$ticketType) {
                return ['success' => false, 'message' => 'Ticket type not found.'];
            }

            // 2. Safely evaluate live inventory amounts
            if ($ticketType->remaining_inventory < $quantity) {
                return ['success' => false, 'message' => 'Requested ticket quantity is no longer available.'];
            }

            // 3. Deduct inventory through the repository layer
            $this->ticketRepository->updateInventory($ticketType, $quantity);

            // 4. Create the 15-minute structural reservation hold
            $reservation = TicketReservation::create([
                'user_id' => $userId,
                'ticket_type_id' => $ticketTypeId,
                'quantity' => $quantity,
                'session_token' => $sessionToken,
                'expires_at' => now()->addMinutes(15),
            ]);

            return [
                'success' => true,
                'message' => 'Tickets reserved successfully for 15 minutes.',
                'reservation_id' => $reservation->id
            ];
        });
    }


    /**
     * Finalizes the reservation by changing its status to 'purchased'.
     */
    public function finalizePurchase(string $sessionToken): array
    {
        return DB::transaction(function () use ($sessionToken) {
            // 1. Find the reservation and lock it to prevent race conditions
            $reservation = TicketReservation::where('session_token', $sessionToken)
                ->where('status', 'reserved')
                ->lockForUpdate()
                ->first();

            // 2. Validate expiration
            if (!$reservation) {
                return ['success' => false, 'message' => 'Reservation not found or already processed.'];
            }

            // Be resilient: if expires_at isn't set (older rows / inconsistent migrations), treat as expired
            if (!$reservation->expires_at || $reservation->expires_at->isPast()) {
                return ['success' => false, 'message' => 'Reservation has expired.'];
            }


            // 3. Transition state
            $reservation->update(['status' => 'purchased']);

            return [
                'success' => true,
                'message' => 'Purchase finalized successfully.'
            ];
        });
    }
}