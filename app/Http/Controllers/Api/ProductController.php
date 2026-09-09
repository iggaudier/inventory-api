<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Traits\ApiResponses;

class ProductController extends Controller
{
    use ApiResponses;
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

        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhereHas('category', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('subcategory', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('brand', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
        });
    }

        $products = $query->paginate($request->input('per_page', 20));

        return $this->ok('Products retrieved successfully', $products);
    }

    public function store(StoreProductRequest $request)
    {
        $user = $request->user();
        $organizationId = $user->isSuperAdmin() ? $request->organization_id : $user->organization_id;

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->storeImage($request->file('image'), $organizationId);
        }

        $product = Product::create([
            ...$data,
            'organization_id' => $organizationId,
            'added_by' => $user->id,
        ]);

        return $this->created('Product created successfully', $product);
    }

    public function show(Product $product)
    {
        $this->authorize('view', $product);

        return $product->load(['category', 'subcategory', 'brand', 'organization:id,name', 'addedBy:id,name']);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($product->image_url) {
                $this->deleteImage($product->image_url);
            }

            $data['image_url'] = $this->storeImage($request->file('image'), $product->organization_id);
        }

        $product->fill($data);
        $product->save();

        return $product;
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        if ($product->image_url) {
            $this->deleteImage($product->image_url);
        }

        $product->delete();

        return $this->ok('Product deleted successfully', $product);
    }

    /**
     * Store an uploaded image on the public disk and return its public URL.
     */
    private function storeImage($file, int $organizationId): string
    {
        $path = $file->store("products/{$organizationId}", 'public');

        return Storage::disk('public')->url($path);
    }

    /**
     * Delete an image given its stored public URL.
     */
    private function deleteImage(string $imageUrl): void
    {
        $disk = Storage::disk('public');
        $baseUrl = rtrim(config('app.url'), '/') . '/storage';

        $path = str_starts_with($imageUrl, $baseUrl)
            ? ltrim(substr($imageUrl, strlen($baseUrl)), '/')
            : $imageUrl;

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }
}