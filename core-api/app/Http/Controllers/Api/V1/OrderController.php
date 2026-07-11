<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Services\OrderService;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    public function index(\Illuminate\Http\Request $request)
    {
        if ($request->has('vendor_id')) {
            $orders = $this->orderService->getByVendor($request->vendor_id);
            return response()->json(['success' => true, 'data' => $orders, 'message' => 'Retrieved successfully']);
        }
        $orders = $this->orderService->getAll();
        return response()->json(['success' => true, 'data' => $orders, 'message' => 'Retrieved successfully']);
    }

    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->create($request->validated());
        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order created successfully',
        ], 201);
    }

    public function show(Order $order)
    {
        return response()->json(['success' => true, 'data' => $order, 'message' => 'Retrieved successfully']);
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        return response()->json([
            'success' => true,
            'data' => $this->orderService->update($order->id, $request->validated()),
            'message' => 'Updated successfully',
        ]);
    }

    public function destroy(Order $order)
    {
        $this->orderService->delete($order->id);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Deleted successfully',
        ]);
    }
}

