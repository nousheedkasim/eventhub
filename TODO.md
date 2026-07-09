# TODO

## Orders (table orders)
- [ ] Add migration for `orders` table
- [ ] Add `Order` model with casts/fillable
- [x] Add `OrderRepositoryInterface` and `OrderRepository`

- [x] Add `OrderService`

- [x] Add `StoreOrderRequest` and `UpdateOrderRequest`

- [x] Add `OrderController`

- [x] Register `apiResource('orders')` in `core-api/routes/api.php`
- [ ] Run `php artisan migrate` and sanity-check basic endpoints (fails in this environment due to MySQL host/Docker config)

## Disputes (table disputes)
- [x] Migration/model/controller/service/repository/requests created for `disputes`
- [x] Registered `apiResource('disputes')` under `/api/v1` with auth:sanctum
- [ ] Test CRUD on Postman (CRED)

## Payments (table payments)

- [x] Migration for `payments` table
- [x] Payment model + casts/relationship
- [x] Payment repository + contract
- [x] StorePaymentRequest + UpdatePaymentRequest
- [x] PaymentController + `apiResource('payments')` route
- [ ] Test Create (C) operation on Postman (POST /api/v1/payments)





