<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function all()
    {
        return Notification::query()->paginate(15);
    }

    public function find($id)
    {
        return Notification::findOrFail($id);
    }

    public function create(array $data)
    {
        return Notification::create($data);
    }

    public function update($id, array $data)
    {
        $notification = Notification::findOrFail($id);
        $notification->update($data);
        return $notification;
    }

    public function delete($id)
    {
        $notification = Notification::findOrFail($id);
        return $notification->delete();
    }
}

