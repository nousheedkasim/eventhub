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
        return response()->json($this->ticketTypeService->getAll());
    }

    public function store(StoreTicketTypeRequest $request)
    {
        return response()->json(
            $this->ticketTypeService->create($request->validated()),
            201
        );
    }

    public function show(TicketType $ticketType)
    {
        return response()->json($ticketType);
    }

    public function update(UpdateTicketTypeRequest $request, TicketType $ticketType)
    {
        return response()->json(
            $this->ticketTypeService->update($ticketType->id, $request->validated())
        );
    }

    public function destroy(TicketType $ticketType)
    {
        $this->ticketTypeService->delete($ticketType->id);

        return response()->json([
            'message' => 'Ticket type deleted successfully',
        ]);
    }
}

