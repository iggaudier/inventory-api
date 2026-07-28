<?php

namespace App\Policies;

use App\Models\Subcategory;
use App\Models\User;

class SubcategoryPolicy
{
    public function view(User $user, Subcategory $subcategory): bool
    {
        return $user->isSuperAdmin() || $subcategory->organization_id === $user->organization_id;
    }

    public function update(User $user, Subcategory $subcategory): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return in_array($user->getRoleNames()->first(), ['group-admin', 'group-member'])
            && $subcategory->organization_id === $user->organization_id;
    }

    public function delete(User $user, Subcategory $subcategory): bool
    {
        return $this->update($user, $subcategory);
    }
}
