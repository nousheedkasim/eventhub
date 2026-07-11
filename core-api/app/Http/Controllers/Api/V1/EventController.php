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
    public function index(\Illuminate\Http\Request $request)
    {
        if ($request->has('vendor_id')) {
            $events = $this->eventService->getByVendor($request->vendor_id);
            return response()->json(['success' => true, 'data' => $events, 'message' => 'Retrieved successfully']);
        }
        $events = $this->eventService->getAll();
        return response()->json(['success' => true, 'data' => $events, 'message' => 'Retrieved successfully']);
    }


    /**
     * Store event.
     */
    public function store(StoreEventRequest $request)
    {
        $data = $request->validated();
        
        $user = $request->user();
        if ($user && $user->type === 'vendor') {
            if (!$user->relationLoaded('vendor')) {
                $user->load('vendor');
            }
            if ($user->vendor) {
                $data['vendor_id'] = $user->vendor->id;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $this->eventService->create($data),
            'message' => 'Created successfully',
        ], 201);
    }


    /**
     * Display event.
     */
    public function show(Event $event)
    {
        return response()->json(['success' => true, 'data' => $event, 'message' => 'Retrieved successfully']);
    }


    /**
     * Update event.
     */
    public function update(
        UpdateEventRequest $request,
        Event $event
    ) {
        return response()->json([
            'success' => true,
            'data' => $this->eventService->update(
                $event->id,
                $request->validated()
            ),
            'message' => 'Updated successfully',
        ]);
    }


    /**
     * Delete event.
     */
    public function destroy(Event $event)
    {
        $this->eventService->delete($event->id);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Deleted successfully',
        ]);
    }
}