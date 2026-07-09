// dbdiagram.io schema (MySQL) derived from Laravel migrations
// Copy everything below into https://dbdiagram.io/home (schema editor)
Project EventTicketing {
  database_type: "MySQL"
}

Table users {
  id bigint [pk, increment]
  full_name varchar(100) [not null]
  email varchar(255) [not null, unique]
  password_hash varchar(255) [not null]
  role enum('admin','vendor','attendee') [not null]
  phone varchar(20)
  is_active boolean [default: true]
  created_at datetime
  updated_at datetime
}

Table vendors {
  id bigint [pk, increment]
  user_id bigint [not null, unique]
  business_name varchar(150)
  kyc_status enum('pending','verified','rejected')
  bank_account_name varchar(150)
  bank_account_number varchar(50)
  bank_name varchar(100)
  bank_code varchar(30)
  created_at datetime
  updated_at datetime
}

Table events {
  id bigint [pk, increment]
  vendor_id bigint [not null]
  title varchar(200)
  description text
  venue varchar(255)
  timezone varchar(50)
  start_at datetime
  end_at datetime
  status enum('draft','published','cancelled','completed')
  created_at datetime
  updated_at datetime
}

Table ticket_types {
  id bigint [pk, increment]
  event_id bigint [not null]
  type varchar(100)
  price decimal(10,2)
  inventory int
  sold_count int [default: 0]
  available_from datetime
  available_until datetime
  is_active boolean [default: true]
  created_at datetime
  updated_at datetime
}

Table orders {
  id bigint [pk, increment]
  attendee_id bigint [not null]
  status enum('pending','held','paid','cancelled','expired','refunded')
  total_amount decimal(10,2)
  hold_expires_at datetime
  created_at datetime
  updated_at datetime
}

Table order_items {
  id bigint [pk, increment]
  order_id bigint [not null]
  ticket_type_id bigint [not null]
  qty int
  price_at_purchase decimal(10,2)
}

Table payments {
  id bigint [pk, increment]
  order_id bigint [not null]
  gateway varchar(50)
  status enum('pending','authorized','paid','failed','refunded')
  idempotency_key varchar(255) [unique]
  gateway_reference varchar(255)
  amount decimal(10,2)
  currency char(3)
  paid_at datetime
  created_at datetime
}

Table refunds {
  id bigint [pk, increment]
  payment_id bigint [not null]
  amount decimal(10,2)
  policy_applied varchar(100)
  status enum('pending','approved','completed','failed')
  reason text
  refunded_at datetime
  created_at datetime
}

Table payout_batches {
  id bigint [pk, increment]
  batch_reference varchar(100) [unique]
  status enum('pending','running','completed','failed')
  total_payouts int
  processed_count int
  resume_token varchar(255)
  started_at datetime
  completed_at datetime
}

Table payouts {
  id bigint [pk, increment]
  vendor_id bigint [not null]
  payout_batch_id bigint
  gross_amount decimal(10,2)
  commission decimal(10,2)
  amount decimal(10,2)
  status enum('pending','processing','paid','failed')
  transfer_reference varchar(255)
  paid_at datetime
  created_at datetime
}

Table notifications {
  id bigint [pk, increment]
  recipient_user_id bigint [not null]
  type varchar(100)
  channel varchar(30)
  status enum('pending','sent','failed')
  retry_count int [default: 0]
  payload text
  sent_at datetime
  created_at datetime
}

Table webhooks {
  id bigint [pk, increment]
  vendor_id bigint [not null]
  url varchar(500)
  secret varchar(255)
  active boolean [default: true]
  created_at datetime
  updated_at datetime
}

Table disputes {
  id bigint [pk, increment]
  order_id bigint [not null]
  status enum('open','investigating','resolved','rejected')
  reason text
  resolution text
  created_at datetime
  resolved_at datetime
}

Ref: vendors.user_id > users.id

Ref: events.vendor_id > vendors.id

Ref: ticket_types.event_id > events.id

Ref: orders.attendee_id > users.id

Ref: order_items.order_id > orders.id

Ref: order_items.ticket_type_id > ticket_types.id

Ref: payments.order_id > orders.id

Ref: refunds.payment_id > payments.id

Ref: payouts.vendor_id > vendors.id

Ref: payouts.payout_batch_id > payout_batches.id

Ref: notifications.recipient_user_id > users.id

Ref: webhooks.vendor_id > vendors.id

Ref: disputes.order_id > orders.id