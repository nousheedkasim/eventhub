<?php

namespace App\Repositories;

use App\Models\OrderItem;
use App\Repositories\Contracts\OrderItemRepositoryInterface;

class OrderItemRepository implements OrderItemRepositoryInterface
{
    public function all()
    {
        return OrderItem::query()->paginate(15);
    }

    public function find($id)
    {
        return OrderItem::findOrFail($id);
    }

    public function create(array $data)
    {
        return OrderItem::create($data);
    }

    public function update($id, array $data)
    {
        $orderItem = OrderItem::findOrFail($id);
        $orderItem->update($data);
        return $orderItem;
    }

    public function delete($id)
    {
        $orderItem = OrderItem::findOrFail($id);
        return $orderItem->delete();
    }
}

