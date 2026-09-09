<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->after('name')->constrained('users')->nullOnDelete();
            $table->dropUnique('brands_name_unique');
        });

        // A formerly global brand may be used by several organizations. Keep
        // every existing product association by creating one brand per org.
        $brands = DB::table('brands')->select('id', 'name', 'created_at', 'updated_at')->orderBy('id')->get();

        foreach ($brands as $brand) {
            $organizationIds = DB::table('products')
                ->where('brand_id', $brand->id)
                ->distinct()
                ->orderBy('organization_id')
                ->pluck('organization_id');

            $primaryOrganizationId = $organizationIds->shift();

            if ($primaryOrganizationId === null) {
                continue;
            }

            DB::table('brands')->where('id', $brand->id)->update([
                'organization_id' => $primaryOrganizationId,
            ]);

            foreach ($organizationIds as $organizationId) {
                $brandId = DB::table('brands')->insertGetId([
                    'organization_id' => $organizationId,
                    'name' => $brand->name,
                    'created_at' => $brand->created_at,
                    'updated_at' => $brand->updated_at,
                ]);

                DB::table('products')
                    ->where('brand_id', $brand->id)
                    ->where('organization_id', $organizationId)
                    ->update(['brand_id' => $brandId]);
            }
        }

        Schema::table('brands', function (Blueprint $table) {
            $table->unique(['organization_id', 'name']);
        });
    }

    public function down(): void
    {
        // Restore the prior global lookup semantics by merging duplicate names
        // before reinstating the global unique index.
        $brandsByName = DB::table('brands')->orderBy('name')->orderBy('id')->get()->groupBy('name');

        foreach ($brandsByName as $brands) {
            $primaryBrand = $brands->shift();

            foreach ($brands as $brand) {
                DB::table('products')->where('brand_id', $brand->id)->update(['brand_id' => $primaryBrand->id]);
                DB::table('brands')->where('id', $brand->id)->delete();
            }
        }

        Schema::table('brands', function (Blueprint $table) {
            $table->dropUnique('brands_organization_id_name_unique');
            $table->unique('name');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
