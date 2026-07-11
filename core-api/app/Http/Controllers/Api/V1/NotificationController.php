<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNotificationRequest;
use App\Http\Requests\UpdateNotificationRequest;
use App\Models\Notification;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function index()
    {
        return response()->json(['success' => true, 'data' => $this->notificationService->getAll(), 'message' => 'Retrieved successfully']);
    }

    public function store(StoreNotificationRequest $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->notificationService->create($request->validated()),
            'message' => 'Created successfully',
        ], 201);
    }

    public function show(Notification $notification)
    {
        return response()->json(['success' => true, 'data' => $notification, 'message' => 'Retrieved successfully']);
    }

    public function update(UpdateNotificationRequest $request, Notification $notification)
    {
        return response()->json([
            'success' => true,
            'data' => $this->notificationService->update($notification->id, $request->validated()),
            'message' => 'Updated successfully',
        ]);
    }

    public function destroy(Notification $notification)
    {
        $this->notificationService->delete($notification->id);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Deleted successfully',
        ]);
    }
}

