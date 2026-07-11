<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TicketType;
use App\Models\Waitlist;
use App\Models\TicketReservation;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessWaitlist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waitlist:process {--ticket-type-id= : Process specific ticket type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process waitlist when tickets become available';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing waitlist entries...');
        
        $ticketTypeId = $this->option('ticket-type-id');
        
        try {
            if ($ticketTypeId) {
                $this->processTicketTypeWaitlist($ticketTypeId);
            } else {
                // Process all ticket types with available inventory
                $ticketTypes = TicketType::whereRaw('inventory - sold_count > 0')
                    ->whereHas('waitlists', function ($query) {
                        $query->where('notified', false);
                    })
                    ->get();
                
                foreach ($ticketTypes as $ticketType) {
                    $this->processTicketTypeWaitlist($ticketType->id);
                }
            }

            $this->info('Waitlist processing completed successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error processing waitlist: ' . $e->getMessage());
            Log::error('Waitlist processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Process waitlist for a specific ticket type
     */
    private function processTicketTypeWaitlist($ticketTypeId)
    {
        $ticketType = TicketType::with('waitlists.user')->find($ticketTypeId);
        
        if (!$ticketType) {
            $this->warn("Ticket type {$ticketTypeId} not found.");
            return;
        }

        $availableInventory = $ticketType->inventory - $ticketType->sold_count;
        $this->info("Processing waitlist for ticket type {$ticketTypeId} ({$availableInventory} available)");

        DB::beginTransaction();
        
        try {
            // Get waitlisted users in priority order who haven't been notified
            $waitlistEntries = Waitlist::where('ticket_type_id', $ticketTypeId)
                ->where('notified', false)
                ->orderBy('priority_index', 'asc')
                ->lockForUpdate()
                ->get();

            $notifiedCount = 0;
            
            foreach ($waitlistEntries as $entry) {
                // Check if inventory is still available
                $ticketType->refresh();
                $availableInventory = $ticketType->inventory - $ticketType->sold_count;
                
                if ($availableInventory <= 0) {
                    $this->info("No more inventory available for ticket type {$ticketTypeId}");
                    break;
                }

                // Notify the user
                $this->notifyWaitlistUser($entry, $ticketType);
                
                // Mark as notified
                $entry->notified = true;
                $entry->save();
                
                $notifiedCount++;
                $this->info("Notified user {$entry->user_id} for ticket type {$ticketTypeId}");
            }

            DB::commit();
            $this->info("Notified {$notifiedCount} waitlist users for ticket type {$ticketTypeId}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Notify waitlist user that tickets are available
     */
    private function notifyWaitlistUser($waitlistEntry, $ticketType)
    {
        $notificationService = app(NotificationService::class);
        
        $notificationService->sendEmailNotification([
            'type' => 'waitlist_available',
            'recipient_email' => $waitlistEntry->user->email,
            'recipient_name' => $waitlistEntry->user->name,
            'data' => [
                'ticket_type_name' => $ticketType->type,
                'event_title' => $ticketType->event->title,
                'available_inventory' => $ticketType->inventory - $ticketType->sold_count,
                'price' => $ticketType->price,
            ]
        ]);

        Log::info('Waitlist notification sent', [
            'waitlist_entry_id' => $waitlistEntry->id,
            'user_id' => $waitlistEntry->user_id,
            'ticket_type_id' => $ticketType->id
        ]);
    }
}
