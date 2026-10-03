<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Models\Organization;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Organization::class);

        $query = Organization::withCount(['users', 'products']);

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = $request->integer('per_page', 20);

        return $this->ok(
            'Organizations retrieved successfully',
            $query->orderBy('name')->paginate($perPage)
        );
    }

    public function store(StoreOrganizationRequest $request)
    {
        $this->authorize('create', Organization::class);

        $organization = Organization::create($request->validated());

        return $this->created('Organization created successfully', $organization);
    }

    public function show(Organization $organization)
    {
        $this->authorize('view', $organization);

        return $this->ok(
            'Organization retrieved successfully',
            $organization->load(['users:id,name,email,organization_id'])
        );
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

        return $this->ok('Organization updated successfully', $organization);
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
            return $this->error('Organization not found.', 404);
        }

        $organization->update([
            'name' => $request->name,
        ]);

        return $this->ok('Organization name updated successfully', $organization);
    }

    public function destroy(Organization $organization)
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        return $this->ok('Organization deleted successfully');
    }
}