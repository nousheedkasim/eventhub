<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\TicketType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupExpiredHolds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cleanup-expired-holds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finds order holds older than 15 minutes, releases inventory, and marks orders as expired';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\OrderService $orderService)
    {
        $this->info('Starting expired holds cleanup...');

        $count = $orderService->cleanupExpiredHolds();

        $this->info("Cleanup completed. Successfully processed {$count} expired orders.");
        return self::SUCCESS;
    }
}
