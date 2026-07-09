<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderItemRequest;
use App\Http\Requests\UpdateOrderItemRequest;
use App\Models\OrderItem;
use App\Services\OrderItemService;

class OrderItemController extends Controller
{
    public function __construct(
        private OrderItemService $orderItemService
    ) {}

    public function index()
    {
        return response()->json($this->orderItemService->getAll());
    }

    public function store(StoreOrderItemRequest $request)
    {
        return response()->json(
            $this->orderItemService->create($request->validated()),
            201
        );
    }

    public function show(OrderItem $orderItem)
    {
        return response()->json($orderItem);
    }

    public function update(UpdateOrderItemRequest $request, OrderItem $orderItem)
    {
        return response()->json(
            $this->orderItemService->update($orderItem->id, $request->validated())
        );
    }

    public function destroy(OrderItem $orderItem)
    {
        $this->orderItemService->delete($orderItem->id);

        return response()->json([
            'message' => 'Order item deleted successfully',
        ]);
    }
}

