# TODO - ReservationFlowTest 500 fix

- [ ] Confirm which server-side exception triggers the 500 (expires_at null vs missing status column).
- [ ] Make `InventoryService::finalizePurchase()` resilient to null `expires_at`.
- [ ] Optionally ensure `reserveTickets()` sets default `status` to `reserved`.
- [ ] Run `php artisan test --filter=ReservationFlowTest` to verify it returns 201 then 200.

