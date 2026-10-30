<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Announcement;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->isSuperAdmin();
    }
}
