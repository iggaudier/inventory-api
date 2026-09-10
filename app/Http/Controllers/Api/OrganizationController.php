<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{

    public function index()
    {
        $this->authorize('viewAny', Organization::class);

        return Organization::withCount('users', 'products')->paginate(20);
    }

    public function store(StoreOrganizationRequest $request)
    {
        $this->authorize('create', Organization::class);

        $organization = Organization::create($request->validated());

        return response()->json($organization, 201);
    }

    public function show(Organization $organization)
    {
        $this->authorize('view', $organization);

        return $organization->load(['users:id,name,email,organization_id']);
    }

    public function update(Request $request, Organization $organization)
    {
        $this->authorize('update', $organization);

        $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:organizations,name,' . $organization->id],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $organization->fill($request->only(['name', 'is_active']));
        $organization->save();

        return response()->json($organization, 200);
    }

    // For Group Admins to update their own organization's name
    public function updateOwnName(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:organizations,name,' . $request->user()->organization_id,
            ],
        ]);

        $organization = $request->user()->organization;

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found.'
            ], 404);
        }

        $organization->update([
            'name' => $request->name,
        ]);

        return response()->json($organization, 200);
    }

    public function destroy(Organization $organization)
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        return response()->json(['message' => 'Organization deleted.']);
    }
}