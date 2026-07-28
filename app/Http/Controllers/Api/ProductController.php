<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Super Admin can see any organization's products (optionally filtered).
     * Group Admin / Group Member see only their own organization's products
     * -- this is the "shared database" between them.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Product::query()->with(['category', 'subcategory', 'brand', 'addedBy:id,name']);

        if ($user->isSuperAdmin()) {
            if ($request->filled('organization_id')) {
                $query->where('organization_id', $request->organization_id);
            }
        } else {
            $query->where('organization_id', $user->organization_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
        }

        return $query->paginate(20);
    }

    public function store(StoreProductRequest $request)
    {
        $user = $request->user();
        $organizationId = $user->isSuperAdmin() ? $request->organization_id : $user->organization_id;

        $product = Product::create([
            ...$request->validated(),
            'organization_id' => $organizationId,
            'added_by' => $user->id,
        ]);

        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        $this->authorize('view', $product);

        return $product->load(['category', 'subcategory', 'brand', 'addedBy:id,name']);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->fill($request->validated());
        $product->save();

        return $product;
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json(['message' => 'Product deleted.']);
    }
}
