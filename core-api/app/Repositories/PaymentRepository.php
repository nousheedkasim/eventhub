<?php

namespace App\Repositories;

use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function all()
    {
        return Payment::query()->paginate(15);
    }

    public function find($id)
    {
        return Payment::findOrFail($id);
    }

    public function create(array $data)
    {
        return Payment::create($data);
    }

    public function update($id, array $data)
    {
        $payment = Payment::findOrFail($id);
        $payment->update($data);
        return $payment;
    }

    public function delete($id)
    {
        $payment = Payment::findOrFail($id);
        return $payment->delete();
    }
}

