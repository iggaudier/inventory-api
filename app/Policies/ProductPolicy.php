<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $user->isSuperAdmin() || $product->organization_id === $user->organization_id;
    }

    public function update(User $user, Product $product): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Group Admin and Group Member share the same organization
        // database and can both edit its products.
        return in_array($user->getRoleNames()->first(), ['group-admin', 'group-member'])
            && $product->organization_id === $user->organization_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}
