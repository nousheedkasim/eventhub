<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create a user for your reservation to link to
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'guest@example.com'],
            [
                'name' => 'Guest User',
                'password' => bcrypt('password'),
            ]
        );

        // 2. Force-create a dummy event
        $event = Event::create([
            'title' => 'Test Concert',
            'slug' => 'test-concert',
            'description' => 'Test',
            'start_time' => now(),
            'end_time' => now(),
            'status' => 'published',
        ]);

        // 3. Force-create a ticket type
        TicketType::create([
            'event_id' => $event->id,
            'name' => 'VIP',
            'price_cents' => 100,
            'total_capacity' => 10,
            'remaining_inventory' => 10,
        ]);
        
        $this->command->info('Seeding finished! User created and event seeded.');
    }
}