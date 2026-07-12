<?php

namespace App\Repositories;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;

class OrderRepository implements OrderRepositoryInterface
{
    public function all()
    {
        return Order::with('items')->paginate(15);
    }

    public function getByVendor($vendorId)
    {
        return Order::with(['items.ticketType.event'])
            ->whereHas('items.ticketType.event', function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId);
            })->paginate(15);
    }

    public function find($id)
    {
        return Order::findOrFail($id);
    }

    public function create(array $data)
    {
        return Order::create($data);
    }

    public function update($id, array $data)
    {
        $order = Order::findOrFail($id);
        $order->update($data);
        return $order;
    }

    public function delete($id)
    {
        $order = Order::findOrFail($id);
        return $order->delete();
    }
}

