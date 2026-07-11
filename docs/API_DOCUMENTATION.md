# EventHub API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication
All protected endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer {token}
```

## Response Format
All endpoints follow a consistent response format:
```json
{
  "success": true,
  "data": {},
  "message": "..."
}
```

---

## Public Endpoints

### Health Check
```
GET /hello
```

**Response:**
```json
{
  "message": "Hello, World!"
}
```

### Register
```
POST /register
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "type": "attendee"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "type": "attendee"
    },
    "token": "sanctum_token_here"
  },
  "message": "Registration successful"
}
```

### Login
```
POST /login
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "sanctum_token_here"
  },
  "message": "Login successful"
}
```

---

## Public Event Endpoints (No Auth Required)

### List Events
```
GET /v1/events
```

**Query Parameters:**
- `status` (optional): Filter by status (draft, published, ongoing, completed, cancelled)
- `vendor_id` (optional): Filter by vendor

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Global Developers Summit 2026",
      "description": "The definitive conference for modern system design.",
      "start_time": "2026-09-15T09:00:00Z",
      "end_time": "2026-09-17T17:00:00Z",
      "timezone": "America/New_York",
      "status": "published",
      "vendor": {
        "id": 1,
        "company_name": "Tech Events Inc."
      }
    }
  ],
  "message": "Events retrieved successfully"
}
```

### Get Event Details
```
GET /v1/events/{event}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Global Developers Summit 2026",
    "description": "The definitive conference for modern system design.",
    "start_time": "2026-09-15T09:00:00Z",
    "end_time": "2026-09-17T17:00:00Z",
    "timezone": "America/New_York",
    "status": "published",
    "vendor": {
      "id": 1,
      "company_name": "Tech Events Inc."
    },
    "ticket_types": [
      {
        "id": 1,
        "name": "Early Bird",
        "price_cents": 29900,
        "total_capacity": 150,
        "remaining_inventory": 120
      }
    ]
  },
  "message": "Event retrieved successfully"
}
```

### Get Event Ticket Types
```
GET /v1/events/{event}/ticket-types
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Early Bird",
      "price_cents": 29900,
      "total_capacity": 150,
      "remaining_inventory": 120
    }
  ],
  "message": "Ticket types retrieved successfully"
}
```

---

## Protected Endpoints (Require Authentication)

### Get Current User
```
GET /user
```

**Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "type": "vendor",
  "vendor": {
    "id": 1,
    "company_name": "Tech Events Inc.",
    "kyc_status": "verified"
  }
}
```

---

## Vendor Endpoints

### Create Event
```
POST /v1/events
```

**Request Body:**
```json
{
  "title": "Global Developers Summit 2026",
  "description": "The definitive conference for modern system design.",
  "start_time": "2026-09-15T09:00:00Z",
  "end_time": "2026-09-17T17:00:00Z",
  "timezone": "America/New_York",
  "status": "draft"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Global Developers Summit 2026",
    "status": "draft",
    "created_at": "2026-07-08T14:50:00Z"
  },
  "message": "Event created successfully"
}
```

### Update Event
```
PUT /v1/events/{event}
```

**Request Body:** Same as create event

### Delete Event
```
DELETE /v1/events/{event}
```

### Create Ticket Type
```
POST /v1/ticket-type
```

**Request Body:**
```json
{
  "event_id": 1,
  "name": "Early Bird",
  "price_cents": 29900,
  "total_capacity": 150
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Early Bird",
    "price_cents": 29900,
    "total_capacity": 150,
    "remaining_inventory": 150
  },
  "message": "Ticket type created successfully"
}
```

### Approve Vendor (Admin Only)
```
POST /v1/vendors/approve
```

**Request Body:**
```json
{
  "vendor_id": 1,
  "status": "verified"
}
```

---

## Order Endpoints

### Create Order
```
POST /v1/orders
```

**Request Body:**
```json
{
  "event_id": 1,
  "items": [
    {
      "ticket_type_id": 1,
      "quantity": 2
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "total_amount_cents": 59800,
    "platform_fee_cents": 5970,
    "status": "pending",
    "reservation_token": "res_v1_9a8b7c6d5e4f3g2h1",
    "expires_at": "2026-07-08T15:05:00Z"
  },
  "message": "Order created successfully"
}
```

### Get Orders
```
GET /v1/orders
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "total_amount_cents": 59800,
      "status": "paid",
      "created_at": "2026-07-08T14:50:00Z",
      "event": {
        "title": "Global Developers Summit 2026"
      },
      "order_items": [
        {
          "ticket_type": {
            "name": "Early Bird"
          },
          "quantity": 2,
          "price_cents": 29900
        }
      ]
    }
  ],
  "message": "Orders retrieved successfully"
}
```

---

## Payment Endpoints

### Process Payment
```
POST /v1/payments
```

**Request Body:**
```json
{
  "order_id": 1,
  "gateway": "stripe",
  "idempotency_key": "pay_v1_order1_20260708"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_id": 1,
    "status": "pending",
    "gateway_transaction_id": "pay_stripe_abc123",
    "amount_cents": 59800
  },
  "message": "Payment initiated successfully"
}
```

---

## Refund Endpoints

### Request Refund
```
POST /v1/refunds
```

**Request Body:**
```json
{
  "order_id": 1,
  "reason": "Accidental medical scheduling conflict"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_id": 1,
    "amount_refunded_cents": 59800,
    "penalty_cents": 0,
    "status": "completed"
  },
  "message": "Refund processed successfully"
}
```

---

## Payout Endpoints

### Create Payout Batch (Admin Only)
```
POST /v1/payout-batches
```

**Request Body:**
```json
{
  "execution_date": "2026-07-08",
  "vendor_ids": [1, 2, 3]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "processing",
    "total_vendors": 3
  },
  "message": "Payout batch created successfully"
}
```

### Get Payouts
```
GET /v1/payouts
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "vendor_id": 1,
      "amount_cents": 50000,
      "status": "completed",
      "created_at": "2026-07-08T00:00:00Z"
    }
  ],
  "message": "Payouts retrieved successfully"
}
```

---

## Webhook Endpoints

### Payment Webhook (Internal)
```
POST /v1/webhooks/payment
```

**Headers:**
```
Authorization: Bearer secure_shared_secret
```

**Request Body:**
```json
{
  "payment_id": 1,
  "status": "success",
  "gateway_transaction_id": "pay_stripe_abc123"
}
```

---

## Admin Endpoints

### Resolve Dispute
```
POST /v1/disputes/{dispute}/resolve
```

**Request Body:**
```json
{
  "resolution": "full_refund",
  "notes": "Customer provided valid medical documentation"
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "success": false,
  "data": null,
  "message": "Unauthenticated"
}
```

### 403 Forbidden
```json
{
  "success": false,
  "data": null,
  "message": "This action is unauthorized"
}
```

### 404 Not Found
```json
{
  "success": false,
  "data": null,
  "message": "Resource not found"
}
```

### 422 Validation Error
```json
{
  "success": false,
  "data": {
    "errors": {
      "email": ["The email field is required."]
    }
  },
  "message": "Validation failed"
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "data": null,
  "message": "Internal server error"
}
```

---

## Testing with cURL

### Register and Login
```bash
# Register
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","password":"password123","type":"attendee"}'

# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'
```

### Create Event (as Vendor)
```bash
curl -X POST http://localhost:8000/api/v1/events \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"title":"My Event","description":"Event description","start_time":"2026-09-15T09:00:00Z","end_time":"2026-09-17T17:00:00Z","timezone":"America/New_York","status":"draft"}'
```

### Create Order
```bash
curl -X POST http://localhost:8000/api/v1/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"event_id":1,"items":[{"ticket_type_id":1,"quantity":2}]}'
```
