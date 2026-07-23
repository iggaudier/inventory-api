<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
 
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();
 
            $table->string('sku');
            $table->string('name');
 
            // category_id, subcategory_id and brand_id now point to rows
            // that belong to a specific organization. The database can't
            // enforce that a product's org matches its category's org —
            // validate that in the FormRequest (e.g. the selected
            // category_id must exist within the authenticated user's
            // organization_id) before saving.
            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();
 
            $table->foreignId('subcategory_id')
                ->nullable()
                ->constrained('subcategories')
                ->nullOnDelete();
 
            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brands')
                ->nullOnDelete();
 
            $table->text('description')->nullable();
            $table->string('material')->nullable();
            $table->string('color')->nullable();
            $table->string('color_hex', 7)->nullable();
 
            // Multi-value attributes; move to normalized tag tables later
            // if you need to filter/search by individual tags at scale.
            $table->json('style_tags')->nullable();
            $table->json('room_tags')->nullable();
 
            $table->decimal('length_cm', 10, 2)->nullable();
            $table->decimal('width_cm', 10, 2)->nullable();
            $table->decimal('height_cm', 10, 2)->nullable();
            $table->decimal('weight_g', 10, 2)->nullable();
 
            $table->decimal('price', 10, 2);
            $table->decimal('trade_price', 10, 2)->nullable();
            $table->char('currency', 3)->default('USD');
 
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->boolean('in_stock')->default(true);
 
            // Stores the S3 object key/path, not a full signed URL.
            $table->string('image_url')->nullable();
 
            $table->string('country_origin')->nullable();
            $table->string('vendor_url')->nullable();
            $table->text('care_instructions')->nullable();
 
            $table->foreignId('added_by')
                ->constrained('users')
                ->restrictOnDelete();
 
            $table->timestamps();
            $table->softDeletes();
 
            // SKU must be unique within an organization, not globally.
            $table->unique(['organization_id', 'sku']);
 
            $table->index('category_id');
            $table->index('brand_id');
            $table->index('in_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
