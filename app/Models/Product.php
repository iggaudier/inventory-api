<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'sku',
        'name',
        'category_id',
        'subcategory_id',
        'brand_id',
        'description',
        'material',
        'color',
        'color_hex',
        'style_tags',
        'room_tags',
        'length_cm',
        'width_cm',
        'height_cm',
        'weight_g',
        'price',
        'trade_price',
        'currency',
        'lead_time_days',
        'in_stock',
        'image_url',
        'country_origin',
        'vendor_url',
        'care_instructions',
        'added_by',
    ];

    protected function casts(): array
    {
        return [
            'style_tags' => 'array',
            'room_tags' => 'array',
            'in_stock' => 'boolean',
            'length_cm' => 'decimal:2',
            'width_cm' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'weight_g' => 'decimal:2',
            'price' => 'decimal:2',
            'trade_price' => 'decimal:2',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
