<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
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
        return response()->json(['success' => true, 'data' => $this->vendorService->getAll(), 'message' => 'Retrieved successfully']);
    }

    public function store(StoreVendorRequest $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->vendorService->create($request->validated()),
            'message' => 'Created successfully',
        ], 201);
    }

    public function show(Vendor $vendor)
    {
        return response()->json(['success' => true, 'data' => $vendor, 'message' => 'Retrieved successfully']);
    }

    // Admin only
    public function update(UpdateVendorRequest $request, Vendor $vendor)
    {
        $this->requireAdmin($request);

        return response()->json([
            'success' => true,
            'data' => $this->vendorService->update($vendor->id, $request->validated()),
            'message' => 'Updated successfully',
        ]);
    }

    // Admin only
    public function destroy(Request $request, Vendor $vendor)
    {
        $this->requireAdmin($request);

        $this->vendorService->delete($vendor->id);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Deleted successfully',
        ]);
    }

    // Admin only operation: approve vendor
    public function approve(Request $request, Vendor $vendor)
    {
        $this->requireAdmin($request);

        $payload = [
            'is_active' => true,
            'kyc_status' => 'verified',
        ];

        if ($request->has('kyc_notes')) {
            $payload['kyc_notes'] = $request->input('kyc_notes');
        }

        return response()->json([
            'success' => true,
            'data' => $this->vendorService->update($vendor->id, $payload),
            'message' => 'Vendor approved successfully',
        ]);
    }

    // Admin only operation: reject vendor
    public function reject(Request $request, Vendor $vendor)
    {
        $this->requireAdmin($request);

        $payload = [
            'is_active' => false,
            'kyc_status' => 'rejected',
        ];

        if ($request->has('kyc_notes')) {
            $payload['kyc_notes'] = $request->input('kyc_notes');
        }

        return response()->json([
            'success' => true,
            'data' => $this->vendorService->update($vendor->id, $payload),
            'message' => 'Vendor rejected successfully',
        ]);
    }

}
