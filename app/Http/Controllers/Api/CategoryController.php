<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Traits\ApiResponses;

class CategoryController extends Controller
{
    /**
     * List categories. Super Admin may pass ?organization_id=, everyone
     * else only ever sees their own organization's categories.
     */
    use ApiResponses;
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Category::query()->with('subcategories');

        if ($user->isSuperAdmin()) {
            if ($request->filled('organization_id')) {
                $query->where('organization_id', $request->organization_id);
            }
        } else {
            $query->where('organization_id', $user->organization_id);
        }

        return $this->ok('Categories retrieved successfully.', $query->get());
    }

    public function store(StoreCategoryRequest $request)
    {
        $user = $request->user();
        $organizationId = $user->isSuperAdmin() ? $request->organization_id : $user->organization_id;

        $category = Category::create([
            'organization_id' => $organizationId,
            'name' => $request->name,
            'created_by' => $user->id,
        ]);

        return response()->json($category, 201);
    }

    public function show(Category $category)
    {
        $this->authorize('view', $category);

        return $category->load('subcategories');
    }

    public function update(Request $request, Category $category)
    {
        $this->authorize('update', $category);

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id . ',id,organization_id,' . $category->organization_id,
        ]);

        // Use direct assignment and save because the model's update method expects no arguments
        $category->name = $request->name;
        $category->save();

        return $category;
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }
}
