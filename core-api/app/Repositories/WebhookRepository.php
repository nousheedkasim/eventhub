<?php

namespace App\Repositories;

use App\Models\Webhook;
use App\Repositories\Contracts\WebhookRepositoryInterface;

class WebhookRepository implements WebhookRepositoryInterface
{
    public function all()
    {
        return Webhook::query()->paginate(15);
    }

    public function find($id)
    {
        return Webhook::findOrFail($id);
    }

    public function create(array $data)
    {
        return Webhook::create($data);
    }

    public function update($id, array $data)
    {
        $webhook = Webhook::findOrFail($id);
        $webhook->update($data);
        return $webhook;
    }

    public function delete($id)
    {
        $webhook = Webhook::findOrFail($id);
        return $webhook->delete();
    }
}

