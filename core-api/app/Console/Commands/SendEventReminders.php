<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SendEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send event reminders to attendees 24 hours before event start';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting event reminder processing...');

        // Find events starting in 24 hours (plus/minus 1 hour window)
        $reminderWindowStart = now()->addHours(23);
        $reminderWindowEnd = now()->addHours(25);

        $events = Event::whereBetween('event_date', [$reminderWindowStart, $reminderWindowEnd])
            ->get();

        $this->info("Found {$events->count()} events needing reminders");

        $reminderCount = 0;

        foreach ($events as $event) {
            $this->info("Processing reminders for Event #{$event->id}: {$event->title}");

            // Get all paid orders for this event
            $orders = Order::where('status', 'paid')
                ->whereHas('items.ticketType', function ($query) use ($event) {
                    $query->where('event_id', $event->id);
                })
                ->with(['items.ticketType', 'attendee'])
                ->get();

            $this->info("Found {$orders->count()} paid orders for this event");

            foreach ($orders as $order) {
                try {
                    // Send reminder notification
                    $this->dispatchEventReminder($event, $order);

                    // Mark reminder as sent
                    DB::table('event_reminders')->insert([
                        'event_id' => $event->id,
                        'order_id' => $order->id,
                        'attendee_id' => $order->attendee_id,
                        'reminder_type' => '24h_before',
                        'sent_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $reminderCount++;

                } catch (\Exception $e) {
                    Log::error("Failed to send reminder for Order #{$order->id}: " . $e->getMessage());
                    $this->error("Failed to send reminder for Order #{$order->id}: " . $e->getMessage());
                }
            }

            $this->info("Sent {$reminderCount} reminders for Event #{$event->id}");
        }

        $this->info("Event reminder processing completed. Sent {$reminderCount} reminders.");
        return self::SUCCESS;
    }

    /**
     * Dispatch event reminder to notification service
     */
    private function dispatchEventReminder($event, $order)
    {
        $notificationUrl = config('services.notification.url', 'http://localhost:3002');
        
        try {
            Http::post($notificationUrl . '/api/notifications/email', [
                'type' => 'event_reminder',
                'data' => [
                    'event_id' => $event->id,
                    'event_name' => $event->title,
                    'event_date' => $event->event_date->toIso8601String(),
                    'event_location' => $event->location,
                    'attendee_email' => $order->attendee->email,
                    'attendee_name' => $order->attendee->name,
                    'order_id' => $order->id,
                    'ticket_count' => $order->items->sum('qty'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to dispatch event reminder: " . $e->getMessage());
            throw $e;
        }
    }
}
