<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\Request;
use App\Traits\ApiResponses;

class BrandController extends Controller
{
    use ApiResponses;
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Brand::query();

        if ($user->isSuperAdmin()) {
            if ($request->filled('organization_id')) {
                $query->where('organization_id', $request->organization_id);
            }
        } else {
            $query->where('organization_id', $user->organization_id);
        }

        return $this->ok('Brands retrieved successfully', $query->get());
    }

    public function store(StoreBrandRequest $request)
    {
        $user = $request->user();
        $organizationId = $user->isSuperAdmin() ? $request->organization_id : $user->organization_id;

        $brand = Brand::create([
            'organization_id' => $organizationId,
            'name' => $request->name,
            'created_by' => $user->id,
        ]);

        return $this->created('Brand created successfully', $brand);
    }

    public function show(Brand $brand)
    {
        $this->authorize('view', $brand);

        return $this->ok('Brand retrieved successfully', $brand);
    }

    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        $brand->update($request->validated());

        return $this->ok('Brand updated successfully', $brand);
    }

    public function destroy(Brand $brand)
    {
        $this->authorize('delete', $brand);

        $brand->delete();

        return $this->ok('Brand deleted successfully', $brand);
    }
}
