<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use App\Http\Requests\ApproveVendorRequest;
use App\Models\Vendor;
use App\Services\VendorService;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function __construct(
        private VendorService $vendorService
    ) {}

    private function requireAdmin(Request $request): void
    {
        if ($request->user()?->type !== 'admin') {
            abort(403, 'Admin only');
        }
    }

    public function index()
    {
        return response()->json($this->vendorService->getAll());
    }

    public function store(StoreVendorRequest $request)
    {
        return response()->json(
            $this->vendorService->create($request->validated()),
            201
        );
    }

    public function show(Vendor $vendor)
    {
        return response()->json($vendor);
    }

    // Admin only
    public function update(UpdateVendorRequest $request, Vendor $vendor)
    {
        $this->requireAdmin($request);

        return response()->json(
            $this->vendorService->update($vendor->id, $request->validated())
        );
    }

    // Admin only
    public function destroy(Request $request, Vendor $vendor)
    {
        $this->requireAdmin($request);

        $this->vendorService->delete($vendor->id);

        return response()->json([
            'message' => 'Vendor deleted successfully',
        ]);
    }

    // Admin only operation: approve vendor
    public function approve(ApproveVendorRequest $request)
    {
        $this->requireAdmin($request);

        $validated = $request->validated();

        $payload = [
            'is_active' => true,
            'kyc_status' => 'verified',
        ];

        if (array_key_exists('kyc_notes', $validated)) {
            $payload['kyc_notes'] = $validated['kyc_notes'];
        }

        return response()->json(
            $this->vendorService->update($validated['vendor_id'], $payload)
        );
    }

}



