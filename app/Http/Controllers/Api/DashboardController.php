<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $user = $request->user();
        $products = Product::query();

        if (! $user->isSuperAdmin()) {
            $products->where('organization_id', $user->organization_id);
        }

        $total = (clone $products)->count();
        $inStock = (clone $products)->where('in_stock', true)->count();

        $categoryMix = (clone $products)
            ->selectRaw('category_id, count(*) as product_count')
            ->whereNotNull('category_id')
            ->with('category:id,name')
            ->groupBy('category_id')
            ->orderByDesc('product_count')
            ->limit(5)
            ->get()
            ->map(fn (Product $product) => [
                'name' => $product->category?->name ?? 'Unclassified',
                'count' => $product->product_count,
            ])
            ->values();

        $recentProducts = (clone $products)
            ->with(['category:id,name', 'brand:id,name'])
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'sku', 'category_id', 'brand_id', 'in_stock', 'created_at']);

        $summary = [
            'total_products' => $total,
            'in_stock' => $inStock,
            'out_of_stock' => $total - $inStock,
            'categories' => (clone $products)->distinct('category_id')->count('category_id'),
            'brands' => (clone $products)->distinct('brand_id')->count('brand_id'),
        ];

        if ($user->isSuperAdmin()) {
            $summary['users'] = User::count();
            $summary['organizations'] = Organization::count();
        } elseif ($user->isGroupAdmin()) {
            $summary['users'] = User::where('organization_id', $user->organization_id)->count();
        }

        return $this->ok('Dashboard retrieved successfully', [
            'summary' => $summary,
            'category_mix' => $categoryMix,
            'recent_products' => $recentProducts,
        ]);
    }
}
