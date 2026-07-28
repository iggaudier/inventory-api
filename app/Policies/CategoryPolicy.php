<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function view(User $user, Category $category): bool
    {
        return $user->isSuperAdmin() || $category->organization_id === $user->organization_id;
    }

    public function update(User $user, Category $category): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return in_array($user->getRoleNames()->first(), ['group-admin', 'group-member'])
            && $category->organization_id === $user->organization_id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->update($user, $category);
    }
}
