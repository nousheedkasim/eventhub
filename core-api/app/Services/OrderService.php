<?php

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderEvent;
use App\Models\TicketType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderService
{
    public function __construct(
        private OrderRepository $repository
    ) {}

    public function getAll()
    {
        return $this->repository->all();
    }

    public function getByVendor($vendorId)
    {
        return $this->repository->getByVendor($vendorId);
    }

    /**
     * Checkout endpoint logic: place a 15-minute hold with distributed locking
     */
    public function create(array $data)
    {
        $items = collect($data['items'])->sortBy('ticket_type_id');
        $locks = [];
        $lockAcquired = true;

        try {
            // 1. Acquire distributed locks in sorted order of ticket type IDs to avoid deadlock
            foreach ($items as $item) {
                $lockKey = "ticket_type_lock_" . $item['ticket_type_id'];
                $lock = Cache::lock($lockKey, 10);
                if (!$lock->get()) {
                    $lockAcquired = false;
                    break;
                }
                $locks[] = $lock;
            }

            if (!$lockAcquired) {
                // Release any locks that were successfully acquired before failing
                foreach ($locks as $l) {
                    $l->release();
                }
                throw new HttpException(409, "Could not acquire lock for ticket inventory. Please try again.");
            }

            // 2. Perform inventory check and reservation inside DB transaction
            return DB::transaction(function () use ($data, $items) {
                $attendeeId = $data['attendee_id'] ?? auth()->id() ?? 1; // fallback if no auth
                $orderItemsData = [];
                $totalAmount = 0;

                foreach ($items as $item) {
                    // Lock the row for update to ensure isolation at the DB layer as well
                    $ticketType = TicketType::with('event')->lockForUpdate()->findOrFail($item['ticket_type_id']);

                    $now = now();
                    // Check if ticket type is active and in its availability window
                    if (!$ticketType->is_active) {
                        throw new HttpException(422, "Ticket type '{$ticketType->type}' is currently inactive.");
                    }

                    if ($now->lt($ticketType->available_from) || $now->gt($ticketType->available_until)) {
                        throw new HttpException(422, "Ticket type '{$ticketType->type}' is outside its availability window.");
                    }

                    // Check capacity limits
                    $availableInventory = $ticketType->inventory - $ticketType->sold_count;
                    if ($availableInventory < $item['qty']) {
                        throw new HttpException(422, "Insufficient inventory for '{$ticketType->type}'. Available: {$availableInventory}, requested: {$item['qty']}.");
                    }

                    // 3. Apply group bundle and early-bird dynamic pricing (all values in cents)
                    $originalPrice = $ticketType->price;
                    $discountBps = 0; // basis points (100 = 1%)
                    $appliedPolicies = [];

                    // Early-bird: purchase made >= 14 days before event date
                    $eventDate = Carbon::parse($ticketType->event->event_date);
                    $daysToEvent = $now->diffInDays($eventDate, false);
                    if ($daysToEvent >= 14) {
                        $discountBps += 1000; // 10%
                        $appliedPolicies[] = "Early Bird 10%";
                    }

                    // Group bundle: buy 4 or more tickets in total (or 4 of this type)
                    $totalQty = $items->sum('qty');
                    if ($totalQty >= 4 || $item['qty'] >= 4) {
                        $discountBps += 2000; // 20%
                        $appliedPolicies[] = "Group Bundle 20%";
                    }

                    // Price in cents: original * (10000 - discountBps) / 10000
                    $finalPrice = (int) round($originalPrice * (10000 - $discountBps) / 10000);

                    // 4. Decrement available inventory (by incrementing sold_count)
                    $ticketType->sold_count += $item['qty'];
                    $ticketType->save();

                    $totalAmount += $finalPrice * $item['qty'];

                    $orderItemsData[] = [
                        'ticket_type_id' => $ticketType->id,
                        'qty' => $item['qty'],
                        'price_at_purchase' => $finalPrice,
                        'policies' => $appliedPolicies
                    ];
                }

                // 5. Create the Order
                $order = Order::create([
                    'attendee_id' => $attendeeId,
                    'status' => 'held',
                    'total_amount' => $totalAmount,
                    'hold_expires_at' => now()->addMinutes(15),
                ]);

                // 6. Create the Order Items
                foreach ($orderItemsData as $itemData) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'ticket_type_id' => $itemData['ticket_type_id'],
                        'qty' => $itemData['qty'],
                        'price_at_purchase' => $itemData['price_at_purchase'],
                    ]);
                }

                // 7. Write financial audit trail event
                OrderEvent::create([
                    'order_id' => $order->id,
                    'from_status' => null,
                    'to_status' => 'held',
                    'payload' => [
                        'message' => 'Order checkout initiated; inventory held for 15 minutes.',
                        'total_amount' => $totalAmount,
                        'items' => $orderItemsData,
                    ],
                ]);

                Log::info("Order #{$order->id} created in 'held' state. Inventory locked.");

                return $order->load('items.ticketType');
            });

        } finally {
            // 8. Release all locks
            foreach ($locks as $l) {
                $l->release();
            }
        }
    }

    public function find($id)
    {
        return $this->repository->find($id);
    }

    public function update($id, array $data)
    {
        // Audit order status changes here if status is updated
        $order = Order::findOrFail($id);
        $oldStatus = $order->status;
        $newStatus = $data['status'] ?? $oldStatus;

        return DB::transaction(function () use ($id, $data, $order, $oldStatus, $newStatus) {
            $updatedOrder = $this->repository->update($id, $data);

            if ($oldStatus !== $newStatus) {
                OrderEvent::create([
                    'order_id' => $order->id,
                    'from_status' => $oldStatus,
                    'to_status' => $newStatus,
                    'payload' => [
                        'message' => "Order status updated via API.",
                        'changes' => $data,
                    ],
                ]);
            }

            return $updatedOrder;
        });
    }

    public function delete($id)
    {
        return $this->repository->delete($id);
    }

    /**
     * Find held orders older than 15 minutes, release inventory, and mark orders expired.
     */
    public function cleanupExpiredHolds(): int
    {
        $expiredOrders = Order::query()
            ->where('status', 'held')
            ->where('hold_expires_at', '<', now())
            ->with('items')
            ->get();

        $count = 0;

        foreach ($expiredOrders as $order) {
            try {
                DB::transaction(function () use ($order) {
                    foreach ($order->items as $item) {
                        // Release inventory
                        $ticketType = TicketType::lockForUpdate()->find($item->ticket_type_id);
                        if ($ticketType) {
                            $ticketType->sold_count = max(0, $ticketType->sold_count - $item->qty);
                            $ticketType->save();
                        }
                    }

                    // Update order status to expired
                    $order->status = 'expired';
                    $order->save();

                    // Audit trail log
                    OrderEvent::create([
                        'order_id' => $order->id,
                        'from_status' => 'held',
                        'to_status' => 'expired',
                        'payload' => [
                            'reason' => '15-minute hold expired without payment.',
                            'released_at' => now()->toDateTimeString()
                        ]
                    ]);

                    Log::info("Order #{$order->id} hold expired. Inventory released.");
                });
                $count++;
            } catch (\Exception $e) {
                Log::error("Failed to release expired hold for order #{$order->id}: " . $e->getMessage());
            }
        }

        return $count;
    }
}
