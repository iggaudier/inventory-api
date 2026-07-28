<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubcategoryRequest;
use App\Models\Subcategory;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Subcategory::query()->with('category');

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

        return $query->get();
    }

    public function store(StoreSubcategoryRequest $request)
    {
        $user = $request->user();
        $organizationId = $user->isSuperAdmin() ? $request->organization_id : $user->organization_id;

        $subcategory = Subcategory::create([
            'organization_id' => $organizationId,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'created_by' => $user->id,
        ]);

        return response()->json($subcategory->load('category'), 201);
    }

    public function show(Subcategory $subcategory)
    {
        $this->authorize('view', $subcategory);

        return $subcategory->load('category');
    }

    public function update(Request $request, Subcategory $subcategory)
    {
        $this->authorize('update', $subcategory);

        $request->validate([
            'name' => 'required|string|max:255|unique:subcategories,name,' . $subcategory->id
                . ',id,organization_id,' . $subcategory->organization_id
                . ',category_id,' . $subcategory->category_id,
        ]);

        $subcategory->name = $request->name;
        $subcategory->save();

        return $subcategory->load('category');
    }

    public function destroy(Subcategory $subcategory)
    {
        $this->authorize('delete', $subcategory);

        $subcategory->delete();

        return response()->json(['message' => 'Subcategory deleted.']);
    }
}
