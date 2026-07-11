<?php

namespace App\Repositories;

use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;

class EventRepository implements EventRepositoryInterface
{

    public function all()
    {
        return Event::all();
    }


    public function getByVendor($vendorId)
    {
        return Event::where('vendor_id', $vendorId)->get();
    }


    public function find($id)
    {
        return Event::findOrFail($id);
    }


    public function create(array $data)
    {
        return Event::create($data);
    }


    public function update($id, array $data)
    {
        $event = Event::findOrFail($id);

        $event->update($data);

        return $event;
    }


    public function delete($id)
    {
        $event = Event::findOrFail($id);

        return $event->delete();
    }
}