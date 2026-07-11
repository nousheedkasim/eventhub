<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\Vendor;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $vendor = Vendor::where('email', 'vendor@example.com')->first();

        Event::create([
            'vendor_id' => $vendor->id,
            'title' => 'Laravel Developer Conference',
            'description' => 'A conference for Laravel developers and backend engineers.',
            'location' => 'Dubai World Trade Centre',
            'event_date' => Carbon::parse('2026-08-15 10:00:00'),
        ]);

        Event::create([
            'vendor_id' => $vendor->id,
            'title' => 'Tech Meetup Dubai',
            'description' => 'Networking event for developers and technology enthusiasts.',
            'location' => 'Dubai Internet City',
            'event_date' => Carbon::parse('2026-09-05 18:30:00'),
        ]);

        Event::create([
            'vendor_id' => $vendor->id,
            'title' => 'Startup Networking Night',
            'description' => 'Meet founders, investors, and entrepreneurs.',
            'location' => 'Dubai Marina',
            'event_date' => Carbon::parse('2026-10-10 19:00:00'),
        ]);

        $this->command->info('Events seeded successfully.');
    }
}
