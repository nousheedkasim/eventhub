<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Day 3: Cleanup expired holds every minute
Schedule::command('orders:cleanup-expired-holds')->everyMinute();

// Day 4: Process payout batches daily at midnight
Schedule::command('payouts:process-batches')->dailyAt('00:00');

// Day 4: Send event reminders hourly (to catch events in 24h window)
Schedule::command('events:send-reminders')->hourly();

// Day 4: Generate sales reports daily at 01:00
Schedule::command('sales:generate-reports')->dailyAt('01:00');

// Day 4: Process waitlist every 5 minutes (periodic fallback)
Schedule::command('waitlist:process')->everyFiveMinutes();
