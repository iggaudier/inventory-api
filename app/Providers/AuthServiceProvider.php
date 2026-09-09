<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Brand;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\BrandPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\ProductPolicy;
use App\Policies\SubcategoryPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Organization::class => OrganizationPolicy::class,
        Product::class => ProductPolicy::class,
        User::class => UserPolicy::class,
        Category::class => CategoryPolicy::class,
        Brand::class => BrandPolicy::class,
        Subcategory::class => SubcategoryPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
