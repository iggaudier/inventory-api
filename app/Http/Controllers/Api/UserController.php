<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGroupAdminRequest;
use App\Http\Requests\StoreGroupMemberRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\TemporaryPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * List users. Super Admin sees everyone (optionally filtered by
     * organization). Group Admin / Group Member see only their own
     * organization's users.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = User::query()->with(['organization:id,name', 'roles:id,name']);

        if (! $user->isSuperAdmin()) {
            $query->where('organization_id', $user->organization_id)->whereNot('id', $user->id);
        } elseif ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        return $query->orderBy('name')->paginate(20);
    }

    /** List the authenticated Group Admin's organization accounts, excluding themselves. */
    public function groupMembers(Request $request)
    {
        $admin = $request->user();
        $query = User::query()
            ->with(['organization:id,name', 'roles:id,name'])
            ->where('organization_id', $admin->organization_id)
            ->whereNot('id', $admin->id)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['group-admin', 'group-member']));

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        return $query->orderBy('name')->paginate(20);
    }

    /**
     * Super Admin creates a Group Admin for a given organization.
     *
     * Kept as-is for whatever existing flow already calls it. New code
     * (the "Add New User" form on the All Users page) should use
     * storeUser() below instead, since it supports picking a role too.
     */
    public function storeGroupAdmin(StoreGroupAdminRequest $request)
    {
        $organization = Organization::findOrFail($request->organization_id);
        $temporaryPassword = Str::password(12);

        $groupAdmin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($temporaryPassword),
            'organization_id' => $organization->id,
            'created_by' => $request->user()->id,
            'must_change_password' => true,
        ]);

        $groupAdmin->assignRole('group-admin');
        $groupAdmin->notify(new TemporaryPasswordNotification($temporaryPassword, $organization->name));

        return response()->json($groupAdmin->load('organization:id,name'), 201);
    }

    /**
     * Group Admin creates a Group Member within their own organization.
     */
    public function storeGroupMember(StoreGroupMemberRequest $request)
    {
        $admin = $request->user();
        $temporaryPassword = Str::password(12);

        $member = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($temporaryPassword),
            'organization_id' => $admin->organization_id,
            'created_by' => $admin->id,
            'must_change_password' => true,
        ]);

        $member->assignRole('group-member');
        $member->notify(new TemporaryPasswordNotification($temporaryPassword, $admin->organization->name));

        return response()->json($member->load('organization:id,name'), 201);
    }

    /**
     * Super Admin creates a user for ANY organization, with EITHER role
     * (group-admin or group-member) — used by the "Add New User" form
     * on the All Users page, where the org and role are both chosen
     * in the UI rather than being implied by who's logged in.
     */
    public function storeUser(StoreUserRequest $request)
    {
        $organization = Organization::findOrFail($request->organization_id);
        $temporaryPassword = Str::password(12);

        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($temporaryPassword),
            'organization_id' => $organization->id,
            'created_by' => $request->user()->id,
            'must_change_password' => true,
        ]);

        $newUser->assignRole($request->role);
        $newUser->notify(new TemporaryPasswordNotification($temporaryPassword, $organization->name));

        return response()->json($newUser->load(['organization:id,name', 'roles:id,name']), 201);
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);

        return $user->load('organization:id,name');
    }

    /**
     * Update a user's name/email, and toggle is_active — this is what
     * powers the inline status toggle on Group Members (a Group Admin
     * flipping their own org's members active/inactive) and, for a
     * Super Admin, editing any user's name/email/status. Who's allowed
     * to touch whom is enforced by UserPolicy::update(); which FIELDS
     * they're allowed to send is enforced right here, per role.
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $actor = $request->user();

        if ($actor->isSuperAdmin()) {
            $data = $request->validate([
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'is_active' => ['sometimes', 'boolean'],
            ]);
        } else {
            // Group Admin may only toggle status — never rename or
            // change another user's email, even if those fields are
            // present in the request body.
            $data = $request->validate([
                'is_active' => ['required', 'boolean'],
            ]);
        }

        $user->fill($data);
        $user->save();

        return $user->load(['organization:id,name', 'roles:id,name']);
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }
}