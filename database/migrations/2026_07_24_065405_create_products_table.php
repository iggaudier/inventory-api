<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Every product belongs to exactly one organization. This is
            // the "shared database" between a Group Admin and its members.
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('sku');
            $table->string('name');

            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();

            $table->text('description')->nullable();
            $table->string('material')->nullable();
            $table->string('color')->nullable();
            $table->string('color_hex', 7)->nullable();

            $table->json('style_tags')->nullable();
            $table->json('room_tags')->nullable();

            $table->decimal('length_cm', 10, 2)->nullable();
            $table->decimal('width_cm', 10, 2)->nullable();
            $table->decimal('height_cm', 10, 2)->nullable();
            $table->decimal('weight_g', 10, 2)->nullable();

            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('trade_price', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');

            $table->unsignedInteger('lead_time_days')->nullable();
            $table->boolean('in_stock')->default(true);

            $table->string('image_url')->nullable(); // S3 URL

            $table->string('country_origin')->nullable();
            $table->string('vendor_url')->nullable();
            $table->text('care_instructions')->nullable();

            // Group Admin or Group Member who created the record.
            $table->foreignId('added_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // SKU must be unique within an organization, but the same SKU
            // string can exist in a different organization.
            $table->unique(['organization_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
