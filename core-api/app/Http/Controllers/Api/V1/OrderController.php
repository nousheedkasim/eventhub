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

    public function index()
    {
        return response()->json($this->orderService->getAll());
    }

    public function store(StoreOrderRequest $request)
    {
        return response()->json(
            $this->orderService->create($request->validated()),
            201
        );
    }

    public function show(Order $order)
    {
        return response()->json($order);
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        return response()->json(
            $this->orderService->update($order->id, $request->validated())
        );
    }

    public function destroy(Order $order)
    {
        $this->orderService->delete($order->id);

        return response()->json([
            'message' => 'Order deleted successfully',
        ]);
    }
}

