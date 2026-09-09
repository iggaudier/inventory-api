<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    public function view(User $user, Brand $brand): bool
    {
        return $user->isSuperAdmin() || $brand->organization_id === $user->organization_id;
    }

    public function update(User $user, Brand $brand): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return in_array($user->getRoleNames()->first(), ['group-admin', 'group-member'])
            && $brand->organization_id === $user->organization_id;
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $this->update($user, $brand);
    }
}
