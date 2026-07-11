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
        return response()->json(['success' => true, 'data' => $this->orderItemService->getAll(), 'message' => 'Retrieved successfully']);
    }

    public function store(StoreOrderItemRequest $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->orderItemService->create($request->validated()),
            'message' => 'Created successfully',
        ], 201);
    }

    public function show(OrderItem $orderItem)
    {
        return response()->json(['success' => true, 'data' => $orderItem, 'message' => 'Retrieved successfully']);
    }

    public function update(UpdateOrderItemRequest $request, OrderItem $orderItem)
    {
        return response()->json([
            'success' => true,
            'data' => $this->orderItemService->update($orderItem->id, $request->validated()),
            'message' => 'Updated successfully',
        ]);
    }

    public function destroy(OrderItem $orderItem)
    {
        $this->orderItemService->delete($orderItem->id);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Deleted successfully',
        ]);
    }
}

