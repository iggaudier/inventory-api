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
