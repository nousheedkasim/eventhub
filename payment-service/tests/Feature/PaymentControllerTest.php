<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'secure_shared_secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.payment.secret' => $this->secret]);
    }

    /**
     * Test payment creation and idempotency enforcement.
     */
    public function test_payment_creation_and_idempotency(): void
    {
        $idempotencyKey = 'test_key_' . uniqid();
        $payload = [
            'order_id' => 123,
            'amount' => 150.00,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'event_date' => now()->addDays(5)->toDateTimeString(),
        ];

        // 1. First request should succeed
        $response = $this->withToken($this->secret)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/payments', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'pending',
            ]);

        $this->assertDatabaseCount('payments', 1);
        $paymentRef = $response->json('payment_reference');

        // 2. Second request with same idempotency key should short-circuit and return identical cached reference
        $secondResponse = $this->withToken($this->secret)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/payments', $payload);

        $secondResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'pending',
                'payment_reference' => $paymentRef,
            ]);

        // Verify no duplicate database entry
        $this->assertDatabaseCount('payments', 1);
    }

    /**
     * Test full refund policy (>48h before event).
     */
    public function test_refund_policy_full_refund(): void
    {
        $eventDate = now()->addHours(72); // > 48h

        $payment = Payment::create([
            'order_id' => 456,
            'amount' => 200.00,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'status' => 'paid',
            'idempotency_key' => 'idemp_key_full_refund',
            'payment_reference' => 'pay_stripe_full_refund',
            'event_date' => $eventDate,
            'refunded_amount' => 0.00,
        ]);

        // Request full refund ($200.00)
        $response = $this->withToken($this->secret)
            ->postJson('/api/payments/refund', [
                'payment_reference' => $payment->payment_reference,
                'amount' => 200.00,
                'event_date' => $eventDate->toDateTimeString(),
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'completed',
                'policy_applied' => 'Full refund (>48h before event)',
            ]);

        $payment->refresh();
        $this->assertEquals(200.00, (float) $payment->refunded_amount);
        $this->assertEquals('refunded', $payment->status);
    }

    /**
     * Test 50% refund policy (24-48h before event) and partial refunds limit.
     */
    public function test_refund_policy_fifty_percent_refund(): void
    {
        $eventDate = now()->addHours(36); // Between 24h and 48h

        $payment = Payment::create([
            'order_id' => 789,
            'amount' => 100.00,
            'currency' => 'USD',
            'gateway' => 'paypal',
            'status' => 'paid',
            'idempotency_key' => 'idemp_key_half_refund',
            'payment_reference' => 'pay_paypal_half_refund',
            'event_date' => $eventDate,
            'refunded_amount' => 0.00,
        ]);

        // Request partial refund of $50.00 (exactly 50%)
        $response = $this->withToken($this->secret)
            ->postJson('/api/payments/refund', [
                'payment_reference' => $payment->payment_reference,
                'amount' => 50.00,
                'event_date' => $eventDate->toDateTimeString(),
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'completed',
                'policy_applied' => '50% refund (24-48h before event)',
            ]);

        // Try to refund another $10.00 (which exceeds the 50% limit of $50.00)
        $failResponse = $this->withToken($this->secret)
            ->postJson('/api/payments/refund', [
                'payment_reference' => $payment->payment_reference,
                'amount' => 10.00,
                'event_date' => $eventDate->toDateTimeString(),
            ]);

        $failResponse->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test refund rejection (<24h before event).
     */
    public function test_refund_policy_rejection(): void
    {
        $eventDate = now()->addHours(12); // < 24h

        $payment = Payment::create([
            'order_id' => 999,
            'amount' => 100.00,
            'currency' => 'USD',
            'gateway' => 'stripe',
            'status' => 'paid',
            'idempotency_key' => 'idemp_key_rejected',
            'payment_reference' => 'pay_stripe_rejected',
            'event_date' => $eventDate,
            'refunded_amount' => 0.00,
        ]);

        // Request refund of $10.00 should be rejected outright
        $response = $this->withToken($this->secret)
            ->postJson('/api/payments/refund', [
                'payment_reference' => $payment->payment_reference,
                'amount' => 10.00,
                'event_date' => $eventDate->toDateTimeString(),
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }
}
