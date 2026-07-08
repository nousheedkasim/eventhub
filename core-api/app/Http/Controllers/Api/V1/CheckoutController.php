<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function reserve(Request $request): JsonResponse
    {
        // Validate payload parameters
        $validated = $request->validate([
            'ticket_type_id' => 'required|integer',
            'quantity' => 'required|integer|min:1|max:10',
        ]);

        // Simulating the authenticated user ID for now (hook onto auth middleware later)
        $userId = $request->user()?->id ?? 1; 
        $sessionToken = Str::random(40);

        $result = $this->inventoryService->reserveTickets(
            $userId,
            $validated['ticket_type_id'],
            $validated['quantity'],
            $sessionToken
        );

        if (!$result['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
            'data' => [
                'reservation_id' => $result['reservation_id'],
                'session_token' => $sessionToken
            ]
        ], 201);
    }

    public function confirm(Request $request): JsonResponse
    {
        // 1. Validate incoming request
        $validated = $request->validate([
            'session_token' => 'required|string|size:40',
        ]);

        // 2. Call the service to finalize the state
        $result = $this->inventoryService->finalizePurchase($validated['session_token']);

        // 3. Handle failure
        if (!$result['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400); // 400 for Bad Request/Logic Error
        }

        // 4. Handle success
        return response()->json([
            'status' => 'success',
            'message' => $result['message']
        ], 200);
    }
}