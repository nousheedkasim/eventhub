<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDisputeRequest;
use App\Http\Requests\UpdateDisputeRequest;
use App\Models\Dispute;
use App\Services\DisputeService;
use Illuminate\Http\Request;

class DisputeController extends Controller
{
    public function __construct(
        private DisputeService $disputeService
    ) {}

    private function requireAdmin(Request $request): void
    {
        if ($request->user()?->type !== 'admin') {
            abort(403, 'Admin only');
        }
    }

    public function index()
    {
        return response()->json(['success' => true, 'data' => $this->disputeService->getAll(), 'message' => 'Retrieved successfully']);
    }

    public function store(StoreDisputeRequest $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->disputeService->create($request->validated()),
            'message' => 'Created successfully',
        ], 201);
    }

    public function show(Dispute $dispute)
    {
        return response()->json(['success' => true, 'data' => $dispute, 'message' => 'Retrieved successfully']);
    }

    // Admin only
    public function update(Request $request, Dispute $dispute)
    {
        $this->requireAdmin($request);

        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'in:open,investigating,resolved,rejected'],
            'reason' => ['sometimes', 'nullable', 'string'],
            'resolution' => ['sometimes', 'nullable', 'string'],
            'resolved_at' => ['sometimes', 'nullable', 'date'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->disputeService->update($dispute->id, $validated),
            'message' => 'Updated successfully',
        ]);
    }

    // Admin only
    public function destroy(Request $request, Dispute $dispute)
    {
        $this->requireAdmin($request);

        $this->disputeService->delete($dispute->id);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Deleted successfully',
        ]);
    }

    // Admin only operation: resolve/reject a dispute
    public function resolve(Request $request, Dispute $dispute)
    {
        $this->requireAdmin($request);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:resolved,rejected'],
            'resolution' => ['required', 'string', 'max:10000'],
            'resolved_at' => ['nullable', 'date'],
        ]);

        $payload = [
            'status' => $validated['status'],
            'resolution' => $validated['resolution'],
            'resolved_at' => $validated['resolved_at'] ?? now(),
        ];

        return response()->json([
            'success' => true,
            'data' => $this->disputeService->update($dispute->id, $payload),
            'message' => 'Dispute resolved successfully',
        ]);
    }
}



