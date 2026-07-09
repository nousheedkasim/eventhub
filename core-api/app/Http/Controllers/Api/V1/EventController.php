<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Services\EventService;

class EventController extends Controller
{
    public function __construct(
        private EventService $eventService
    ) {}

    /**
     * Display a listing of events.
     */
    public function index()
    {
        return response()->json(
            $this->eventService->getAll()
        );
    }


    /**
     * Store event.
     */
    public function store(StoreEventRequest $request)
    {
        return response()->json(
            $this->eventService->create(
                $request->validated()
            ),
            201
        );
    }


    /**
     * Display event.
     */
    public function show(Event $event)
    {
        return response()->json($event);
    }


    /**
     * Update event.
     */
    public function update(
        UpdateEventRequest $request,
        Event $event
    ) {
        return response()->json(
            $this->eventService->update(
                $event->id,
                $request->validated()
            )
        );
    }


    /**
     * Delete event.
     */
    public function destroy(Event $event)
    {
        $this->eventService->delete($event->id);

        return response()->json([
            'message' => 'Event deleted successfully'
        ]);
    }
}