<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE ticket_types MODIFY price BIGINT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('UPDATE ticket_types SET price = price * 100');

        DB::statement('ALTER TABLE orders MODIFY total_amount BIGINT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('UPDATE orders SET total_amount = total_amount * 100');

        DB::statement('ALTER TABLE order_items MODIFY price_at_purchase BIGINT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('UPDATE order_items SET price_at_purchase = price_at_purchase * 100');

        DB::statement('ALTER TABLE payments MODIFY amount BIGINT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('UPDATE payments SET amount = amount * 100');

        DB::statement('ALTER TABLE refunds MODIFY amount BIGINT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('UPDATE refunds SET amount = amount * 100');

        DB::statement('ALTER TABLE payouts MODIFY gross_amount BIGINT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE payouts MODIFY commission BIGINT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE payouts MODIFY amount BIGINT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('UPDATE payouts SET gross_amount = gross_amount * 100, commission = commission * 100, amount = amount * 100');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('UPDATE ticket_types SET price = price / 100');
        DB::statement('ALTER TABLE ticket_types MODIFY price DECIMAL(10,2) NOT NULL DEFAULT 0');

        DB::statement('UPDATE orders SET total_amount = total_amount / 100');
        DB::statement('ALTER TABLE orders MODIFY total_amount DECIMAL(10,2) NOT NULL DEFAULT 0');

        DB::statement('UPDATE order_items SET price_at_purchase = price_at_purchase / 100');
        DB::statement('ALTER TABLE order_items MODIFY price_at_purchase DECIMAL(10,2) NOT NULL DEFAULT 0');

        DB::statement('UPDATE payments SET amount = amount / 100');
        DB::statement('ALTER TABLE payments MODIFY amount DECIMAL(10,2) NOT NULL DEFAULT 0');

        DB::statement('UPDATE refunds SET amount = amount / 100');
        DB::statement('ALTER TABLE refunds MODIFY amount DECIMAL(10,2) NOT NULL DEFAULT 0');

        DB::statement('UPDATE payouts SET gross_amount = gross_amount / 100, commission = commission / 100, amount = amount / 100');
        DB::statement('ALTER TABLE payouts MODIFY gross_amount DECIMAL(10,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE payouts MODIFY commission DECIMAL(10,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE payouts MODIFY amount DECIMAL(10,2) NOT NULL DEFAULT 0');
    }
};
