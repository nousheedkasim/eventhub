<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use App\Models\Vendor;
use App\Services\VendorService;

class VendorController extends Controller
{
    public function __construct(
        private VendorService $vendorService
    ) {}

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

    public function update(UpdateVendorRequest $request, Vendor $vendor)
    {
        return response()->json(
            $this->vendorService->update($vendor->id, $request->validated())
        );
    }

    public function destroy(Vendor $vendor)
    {
        $this->vendorService->delete($vendor->id);

        return response()->json([
            'message' => 'Vendor deleted successfully',
        ]);
    }
}

