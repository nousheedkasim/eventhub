<?php

namespace App\Console\Commands;

use App\Models\TicketReservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupExpiredReservations extends Command
{
    protected $signature = 'app:cleanup-expired-reservations';

    protected $description = 'Release expired ticket reservations and return inventory to stock';

    public function handle()
    {
        // 1. Fetch all reservations that have passed their expiration time
        $expiredReservations = TicketReservation::where('expires_at', '<', now())->get();

        if ($expiredReservations->isEmpty()) {
            $this->info('No expired reservations found.');
            return;
        }

        $count = 0;

        foreach ($expiredReservations as $reservation) {
            DB::transaction(function () use ($reservation) {
                // 2. Increment the remaining_inventory back in the ticket_types table
                // Note: We use the relationship defined in your Model
                $reservation->ticketType()->increment('remaining_inventory', $reservation->quantity);
                
                // 3. Delete the reservation record
                $reservation->delete();
            });
            $count++;
        }

        $this->info("Successfully cleaned up {$count} expired reservations.");
    }
}