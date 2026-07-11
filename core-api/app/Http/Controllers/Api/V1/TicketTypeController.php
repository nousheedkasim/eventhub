<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketTypeRequest;
use App\Http\Requests\UpdateTicketTypeRequest;
use App\Models\TicketType;
use App\Services\TicketTypeService;

class TicketTypeController extends Controller
{
    public function __construct(
        private TicketTypeService $ticketTypeService
    ) {}

    public function index()
    {
        return response()->json(['success' => true, 'data' => $this->ticketTypeService->getAll(), 'message' => 'Retrieved successfully']);
    }

    public function store(StoreTicketTypeRequest $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->ticketTypeService->create($request->validated()),
            'message' => 'Created successfully',
        ], 201);
    }

    public function show(TicketType $ticketType)
    {
        return response()->json(['success' => true, 'data' => $ticketType, 'message' => 'Retrieved successfully']);
    }

    public function getByEvent($eventId)
    {
        return response()->json([
            'success' => true,
            'data' => $this->ticketTypeService->getByEvent($eventId),
            'message' => 'Retrieved successfully',
        ]);
    }

    public function update(UpdateTicketTypeRequest $request, TicketType $ticketType)
    {
        return response()->json([
            'success' => true,
            'data' => $this->ticketTypeService->update($ticketType->id, $request->validated()),
            'message' => 'Updated successfully',
        ]);
    }

    public function destroy(TicketType $ticketType)
    {
        $this->ticketTypeService->delete($ticketType->id);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Deleted successfully',
        ]);
    }
}

