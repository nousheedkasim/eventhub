<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected array $payload
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $url = config('services.payment.core_url') . '/api/v1/webhooks/payment';
        $secret = config('services.payment.secret');

        Log::info("Sending webhook to core API: {$url} with payload: " . json_encode($this->payload));

        try {
            $response = Http::withToken($secret)
                ->post($url, $this->payload);

            if ($response->failed()) {
                Log::error("Failed to deliver payment webhook to core API. Status: " . $response->status() . " Response: " . $response->body());
                // Retry in 10 seconds
                $this->release(10);
            } else {
                Log::info("Successfully delivered payment webhook to core API.");
            }
        } catch (\Exception $e) {
            Log::error("Error delivering payment webhook to core API: " . $e->getMessage());
            // Retry in 15 seconds
            $this->release(15);
        }
    }
}
