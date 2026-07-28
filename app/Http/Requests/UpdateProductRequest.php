<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    public function rules(): array
    {
        $organizationId = $this->route('product')->organization_id;

        return [
            'sku' => [
                'sometimes', 'required', 'string', 'max:100',
                Rule::unique('products', 'sku')
                    ->where(fn ($q) => $q->where('organization_id', $organizationId))
                    ->ignore($this->route('product')->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],

            'category_id' => [
                'nullable', 'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('organization_id', $organizationId)),
            ],
            'subcategory_id' => [
                'nullable', 'integer',
                Rule::exists('subcategories', 'id')->where(function ($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId);
                    if ($this->filled('category_id')) {
                        $q->where('category_id', $this->input('category_id'));
                    }
                }),
            ],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],

            'description' => ['nullable', 'string'],
            'material' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:100'],
            'color_hex' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            'style_tags' => ['nullable', 'array'],
            'room_tags' => ['nullable', 'array'],

            'length_cm' => ['nullable', 'numeric', 'min:0'],
            'width_cm' => ['nullable', 'numeric', 'min:0'],
            'height_cm' => ['nullable', 'numeric', 'min:0'],
            'weight_g' => ['nullable', 'numeric', 'min:0'],

            'price' => ['nullable', 'numeric', 'min:0'],
            'trade_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],

            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'in_stock' => ['boolean'],

            'image_url' => ['nullable', 'url', 'max:2048'],
            'country_origin' => ['nullable', 'string', 'max:100'],
            'vendor_url' => ['nullable', 'url', 'max:2048'],
            'care_instructions' => ['nullable', 'string'],
        ];
    }
}
