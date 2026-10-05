<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $actor, User $target): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        // Group Admin can view members of their own organization.
        if ($actor->isGroupAdmin()) {
            return $target->organization_id === $actor->organization_id;
        }

        return $actor->id === $target->id;
    }

    /**
     * Controls who may call PUT /users/{user}. Field-level restriction
     * (e.g. a Group Admin only ever being allowed to send `is_active`,
     * never `name`/`email`) is enforced in UserController@update, not
     * here — this only decides whether the actor may touch the target
     * user at all.
     */
    public function update(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false; // no self-editing via this endpoint
        }

        if ($actor->isSuperAdmin()) {
            // Super Admin can update any Group Admin or Group Member,
            // but not another Super Admin — mirrors delete()'s same rule.
            return ! $target->isSuperAdmin();
        }

        // Group Admin can only update Group Members within their own org.
        if ($actor->isGroupAdmin()) {
            return $target->isGroupMember() && $target->organization_id === $actor->organization_id;
        }

        return false;
    }

    public function delete(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false; // no self-deletion
        }

        if ($actor->isSuperAdmin()) {
            // Super Admin can remove any Group Admin or Group Member.
            return ! $target->isSuperAdmin();
        }

        // Group Admin can only remove Group Members within their own org.
        if ($actor->isGroupAdmin()) {
            return $target->isGroupMember() && $target->organization_id === $actor->organization_id;
        }

        return false;
    }
}