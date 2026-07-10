<?php

// Test script for EventHub API endpoints
$baseUrl = 'http://localhost:8000';
$paymentUrl = 'http://localhost:8001';
$sharedSecret = 'secure_shared_secret';

function makeRequest($url, $method, $data = null, $token = null, $sharedSecret = null) {
    $ch = curl_init();
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    if ($sharedSecret) {
        $headers[] = "Authorization: Bearer $sharedSecret";
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'body' => json_decode($response, true),
        'raw' => $response
    ];
}

echo "=== EventHub API Endpoint Testing ===\n\n";

// 1. Register User
echo "1. Testing User Registration...\n";
$result = makeRequest("$baseUrl/api/register", 'POST', [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => 'password123'
]);
echo "Status: {$result['status']}\n";
echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";
$token = $result['body']['token'] ?? null;

if (!$token) {
    echo "Failed to get token. Trying login...\n";
    $result = makeRequest("$baseUrl/api/login", 'POST', [
        'email' => 'test@example.com',
        'password' => 'password123'
    ]);
    echo "Login Status: {$result['status']}\n";
    echo "Login Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";
    $token = $result['body']['token'] ?? null;
}

if (!$token) {
    die("Could not authenticate. Exiting.\n");
}

echo "Token: $token\n\n";

// 2. Create Event
echo "2. Testing Event Creation...\n";
$result = makeRequest("$baseUrl/api/v1/events", 'POST', [
    'title' => 'Test Conference 2026',
    'description' => 'A test conference for API testing',
    'location' => 'Dubai Convention Center',
    'event_date' => '2026-08-15T10:00:00Z'
], $token);
echo "Status: {$result['status']}\n";
echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";
$eventId = $result['body']['id'] ?? 1;

// 3. Create Ticket Type
echo "3. Testing Ticket Type Creation...\n";
$result = makeRequest("$baseUrl/api/v1/ticket-type", 'POST', [
    'event_id' => $eventId,
    'type' => 'General Admission',
    'price' => 100.00,
    'inventory' => 50,
    'available_from' => '2026-07-01T00:00:00Z',
    'available_until' => '2026-08-14T23:59:59Z',
    'is_active' => true
], $token);
echo "Status: {$result['status']}\n";
echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";
$ticketTypeId = $result['body']['id'] ?? 1;

// 4. Create Order (15-minute hold)
echo "4. Testing Order Creation (15-minute hold)...\n";
$result = makeRequest("$baseUrl/api/v1/orders", 'POST', [
    'attendee_id' => 1,
    'items' => [
        [
            'ticket_type_id' => $ticketTypeId,
            'qty' => 2
        ]
    ]
], $token);
echo "Status: {$result['status']}\n";
echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";
$orderId = $result['body']['id'] ?? 1;

// 5. Create Payment
echo "5. Testing Payment Creation (calls payment service)...\n";
$result = makeRequest("$baseUrl/api/v1/payments", 'POST', [
    'order_id' => $orderId,
    'gateway' => 'stripe'
], $token);
echo "Status: {$result['status']}\n";
echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";
$paymentReference = $result['body']['gateway_reference'] ?? null;

// 6. Test Webhook Callback (simulate payment success)
echo "6. Testing Payment Webhook Callback...\n";
$result = makeRequest("$baseUrl/api/v1/webhooks/payment", 'POST', [
    'order_id' => $orderId,
    'payment_reference' => $paymentReference ?? 'pay_stripe_test_' . time(),
    'status' => 'paid',
    'amount' => 180.00,
    'currency' => 'USD'
], null, $sharedSecret);
echo "Status: {$result['status']}\n";
echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";

// 7. Test Refund via Payment Service
echo "7. Testing Refund (via payment service)...\n";
$result = makeRequest("$paymentUrl/api/payments/refund", 'POST', [
    'payment_reference' => $paymentReference ?? 'pay_stripe_test_' . time(),
    'amount' => 90.00,
    'event_date' => '2026-08-15T10:00:00Z'
], null, $sharedSecret);
echo "Status: {$result['status']}\n";
echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";

// 8. Test Group Bundle Pricing
echo "8. Testing Group Bundle Pricing (4+ tickets)...\n";
$result = makeRequest("$baseUrl/api/v1/orders", 'POST', [
    'attendee_id' => 1,
    'items' => [
        [
            'ticket_type_id' => $ticketTypeId,
            'qty' => 4
        ]
    ]
], $token);
echo "Status: {$result['status']}\n";
echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n";
echo "Expected: 4 tickets * $100 = $400, with 30% discount (10% early-bird + 20% group) = $280\n\n";

// 9. Test Inventory Limit
echo "9. Testing Inventory Capacity Limit...\n";
$result = makeRequest("$baseUrl/api/v1/orders", 'POST', [
    'attendee_id' => 1,
    'items' => [
        [
            'ticket_type_id' => $ticketTypeId,
            'qty' => 100  // More than available
        ]
    ]
], $token);
echo "Status: {$result['status']}\n";
echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";

// 10. Get Order Status
echo "10. Testing Get Order Status...\n";
$result = makeRequest("$baseUrl/api/v1/orders/$orderId", 'GET', null, $token);
echo "Status: {$result['status']}\n";
echo "Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n\n";

echo "=== Testing Complete ===\n";
