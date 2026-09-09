<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Creates one fully-wired demo organization so the API can be
     * exercised end-to-end (e.g. via the Postman collection) without
     * manually clicking through the account-creation flow first.
     *
     * Credentials here are fixed/known on purpose for testing and set
     * must_change_password = false so the seeded accounts can call
     * protected endpoints immediately. Real accounts created through
     * POST /group-admins and POST /group-members still get a random
     * temp password and must_change_password = true, as designed.
     */
    public function run(): void
    {
        $organization = Organization::firstOrCreate(
            ['name' => 'Acme Interiors'],
            ['is_active' => true]
        );

        $groupAdmin = User::firstOrCreate(
            ['email' => 'admin@acme-interiors.test'],
            [
                'name' => 'Alice Admin',
                'password' => Hash::make('Password123!'),
                'organization_id' => $organization->id,
                'must_change_password' => false,
            ]
        );
        if (! $groupAdmin->hasRole('group-admin')) {
            $groupAdmin->assignRole('group-admin');
        }

        $groupMember = User::firstOrCreate(
            ['email' => 'member@acme-interiors.test'],
            [
                'name' => 'Mike Member',
                'password' => Hash::make('Password123!'),
                'organization_id' => $organization->id,
                'created_by' => $groupAdmin->id,
                'must_change_password' => false,
            ]
        );
        if (! $groupMember->hasRole('group-member')) {
            $groupMember->assignRole('group-member');
        }

        $brand = Brand::firstOrCreate(
            ['name' => 'Acme Furniture Co.', 'organization_id' => $organization->id],
            ['created_by' => $groupAdmin->id]
        );

        $category = Category::firstOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Furniture'],
            ['created_by' => $groupAdmin->id]
        );

        $subcategory = Subcategory::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'category_id' => $category->id,
                'name' => 'Sofa',
            ],
            ['created_by' => $groupAdmin->id]
        );

        Product::firstOrCreate(
            ['organization_id' => $organization->id, 'sku' => 'SOF-001'],
            [
                'name' => 'Chesterfield 3-Seater Sofa',
                'category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'brand_id' => $brand->id,
                'description' => 'Classic tufted leather Chesterfield sofa.',
                'material' => 'Leather',
                'color' => 'Tan',
                'color_hex' => '#B5651D',
                'style_tags' => ['classic', 'chesterfield'],
                'room_tags' => ['living-room'],
                'length_cm' => 210.00,
                'width_cm' => 90.00,
                'height_cm' => 85.00,
                'weight_g' => 45000.00,
                'price' => 2499.00,
                'trade_price' => 1799.00,
                'currency' => 'USD',
                'lead_time_days' => 14,
                'in_stock' => true,
                'country_origin' => 'Italy',
                'vendor_url' => 'https://vendor.example.com/sofa-001',
                'care_instructions' => 'Wipe clean with a damp cloth.',
                'added_by' => $groupAdmin->id,
            ]
        );
    }
}
