<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\TicketType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TicketTypeSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::all();

        foreach ($events as $event) {
            // General Admission
            TicketType::create([
                'event_id' => $event->id,
                'type' => 'General Admission',
                'price' => 9900,
                'inventory' => 200,
                'sold_count' => 0,
                'available_from' => Carbon::parse($event->event_date)->subDays(60),
                'available_until' => Carbon::parse($event->event_date)->subHours(1),
                'is_active' => true,
            ]);

            // VIP
            TicketType::create([
                'event_id' => $event->id,
                'type' => 'VIP',
                'price' => 24900,
                'inventory' => 50,
                'sold_count' => 0,
                'available_from' => Carbon::parse($event->event_date)->subDays(60),
                'available_until' => Carbon::parse($event->event_date)->subHours(1),
                'is_active' => true,
            ]);

            // Early Bird
            TicketType::create([
                'event_id' => $event->id,
                'type' => 'Early Bird',
                'price' => 5900,
                'inventory' => 100,
                'sold_count' => 0,
                'available_from' => Carbon::parse($event->event_date)->subDays(90),
                'available_until' => Carbon::parse($event->event_date)->subDays(14),
                'is_active' => true,
            ]);

            // Group Bundle (4+ tickets)
            TicketType::create([
                'event_id' => $event->id,
                'type' => 'Group Bundle (4+)',
                'price' => 7900,
                'inventory' => 150,
                'sold_count' => 0,
                'available_from' => Carbon::parse($event->event_date)->subDays(60),
                'available_until' => Carbon::parse($event->event_date)->subHours(1),
                'is_active' => true,
            ]);
        }

        $this->command->info('Ticket types seeded successfully.');
    }
}
