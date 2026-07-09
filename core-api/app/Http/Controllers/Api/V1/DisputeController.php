<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDisputeRequest;
use App\Http\Requests\UpdateDisputeRequest;
use App\Models\Dispute;
use App\Services\DisputeService;

class DisputeController extends Controller
{
    public function __construct(
        private DisputeService $disputeService
    ) {}

    public function index()
    {
        return response()->json($this->disputeService->getAll());
    }

    public function store(StoreDisputeRequest $request)
    {
        return response()->json(
            $this->disputeService->create($request->validated()),
            201
        );
    }

    public function show(Dispute $dispute)
    {
        return response()->json($dispute);
    }

    public function update(UpdateDisputeRequest $request, Dispute $dispute)
    {
        return response()->json(
            $this->disputeService->update($dispute->id, $request->validated())
        );
    }

    public function destroy(Dispute $dispute)
    {
        $this->disputeService->delete($dispute->id);

        return response()->json([
            'message' => 'Dispute deleted successfully',
        ]);
    }
}

