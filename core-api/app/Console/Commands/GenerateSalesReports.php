<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Event;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateSalesReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:generate-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily sales reports per vendor and platform-wide';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting daily sales report generation...');
        
        $yesterday = Carbon::yesterday()->startOfDay();
        $today = Carbon::today()->startOfDay();

        try {
            // Generate platform-wide report
            $platformStats = $this->generatePlatformReport($yesterday, $today);
            $this->info("Platform-wide sales: {$platformStats['total_orders']} orders, {$platformStats['total_revenue']} cents revenue");

            // Generate per-vendor reports
            $vendors = Vendor::all();

            foreach ($vendors as $vendor) {
                $vendorStats = $this->generateVendorReport($vendor, $yesterday, $today);
                $this->info("Vendor {$vendor->id}: {$vendorStats['total_orders']} orders, {$vendorStats['total_revenue']} cents revenue");
                
                // Store report in database (could create a sales_reports table)
                // For now, we'll log it
                Log::info('Vendor sales report', [
                    'vendor_id' => $vendor->id,
                    'date' => $yesterday->toDateString(),
                    'stats' => $vendorStats
                ]);
            }

            $this->info('Sales report generation completed successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error generating sales reports: ' . $e->getMessage());
            Log::error('Sales report generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Generate platform-wide sales report
     */
    private function generatePlatformReport($startDate, $endDate)
    {
        return Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue
            ')
            ->first()
            ->toArray();
    }

    /**
     * Generate vendor-specific sales report
     */
    private function generateVendorReport($vendor, $startDate, $endDate)
    {
        // Simplified version - just get order count for vendor's events
        $vendorEventIds = \App\Models\Event::where('vendor_id', $vendor->id)->pluck('id');
        $ticketTypeIds = \App\Models\TicketType::whereIn('event_id', $vendorEventIds)->pluck('id');
        $orderItemIds = \App\Models\OrderItem::whereIn('ticket_type_id', $ticketTypeIds)->pluck('order_id');
        
        return Order::whereIn('id', $orderItemIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue
            ')
            ->first()
            ->toArray();
    }
}
