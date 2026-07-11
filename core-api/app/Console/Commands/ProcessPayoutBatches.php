<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vendor;
use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ProcessPayoutBatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payouts:process-batches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process vendor payout batches for vendors who meet minimum threshold';

    /**
     * Execute the console command.
     */
    public function handle(PayoutService $payoutService)
    {
        $this->info('Starting payout batch processing...');

        $commissionRate = config('payout.commission_rate', 0.10);
        $minThreshold = config('payout.minimum_threshold', 5000); // cents

        // Get vendors with pending payouts who meet threshold
        $vendors = Vendor::where('is_active', true)
            ->where('kyc_status', 'verified')
            ->whereHas('payouts', function ($query) {
                $query->where('status', 'pending');
            })
            ->with(['payouts' => function ($query) {
                $query->where('status', 'pending');
            }])
            ->get();

        $processedCount = 0;

        foreach ($vendors as $vendor) {
            $pendingPayouts = $vendor->payouts;
            $totalPendingAmount = $pendingPayouts->sum('amount');

            // Calculate net amount after commission
            $result = $payoutService->calculatePayout($totalPendingAmount, $commissionRate, $minThreshold);

            if (!$result['eligible']) {
                $this->line("Vendor #{$vendor->id} does not meet minimum threshold (Net: {$result['amount']}, Threshold: {$minThreshold})");
                continue;
            }

            $this->info("Processing payout for Vendor #{$vendor->id}: Gross: {$result['gross_amount']}, Net: {$result['amount']}");

            try {
                DB::transaction(function () use ($vendor, $pendingPayouts, $result, $commissionRate) {
                    // Mark all pending payouts as processed
                    foreach ($pendingPayouts as $payout) {
                        $payout->status = 'paid';
                        $payout->commission = (int) round($payout->amount * $commissionRate);
                        $payout->amount = $payout->amount - $payout->commission;
                        $payout->paid_at = now();
                        $payout->save();
                    }

                    // Send notification to notification service
                    $this->dispatchPayoutNotification($vendor, $result);
                });

                $processedCount++;
                $this->info("Successfully processed payouts for Vendor #{$vendor->id}");

            } catch (\Exception $e) {
                Log::error("Failed to process payouts for Vendor #{$vendor->id}: " . $e->getMessage());
                $this->error("Failed to process payouts for Vendor #{$vendor->id}: " . $e->getMessage());
            }
        }

        $this->info("Payout batch processing completed. Processed {$processedCount} vendors.");
        return self::SUCCESS;
    }

    /**
     * Dispatch payout notification to notification service
     */
    private function dispatchPayoutNotification($vendor, $payoutResult)
    {
        $notificationUrl = config('services.notification.url', 'http://localhost:3002');
        
        try {
            Http::post($notificationUrl . '/api/notifications/email', [
                'type' => 'payout_notification',
                'data' => [
                    'vendor_id' => $vendor->id,
                    'vendor_email' => $vendor->email,
                    'payout_id' => $vendor->payouts->first()->id,
                    'amount' => $payoutResult['amount'],
                    'currency' => 'USD',
                    'commission' => $payoutResult['commission'],
                    'gross_amount' => $payoutResult['gross_amount'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to dispatch payout notification: " . $e->getMessage());
            // Don't throw - payout processing should succeed even if notification fails
        }
    }
}
