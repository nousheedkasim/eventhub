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
        return response()->json($this->notificationService->getAll());
    }

    public function store(StoreNotificationRequest $request)
    {
        return response()->json(
            $this->notificationService->create($request->validated()),
            201
        );
    }

    public function show(Notification $notification)
    {
        return response()->json($notification);
    }

    public function update(UpdateNotificationRequest $request, Notification $notification)
    {
        return response()->json(
            $this->notificationService->update($notification->id, $request->validated())
        );
    }

    public function destroy(Notification $notification)
    {
        $this->notificationService->delete($notification->id);

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }
}

