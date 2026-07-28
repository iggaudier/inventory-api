<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Super Admin, Group Admin, and Group Member may create products.
        // Organization scoping is enforced in the controller/policy.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $organizationId = $this->user()->isSuperAdmin()
            ? $this->input('organization_id')
            : $this->user()->organization_id;

        return [
            'organization_id' => $this->user()->isSuperAdmin()
                ? ['required', 'integer', 'exists:organizations,id']
                : ['sometimes', 'prohibited'],

            'sku' => [
                'required', 'string', 'max:100',
                Rule::unique('products', 'sku')->where(fn ($q) => $q->where('organization_id', $organizationId)),
            ],
            'name' => ['required', 'string', 'max:255'],

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
            'style_tags.*' => ['string', 'max:100'],
            'room_tags' => ['nullable', 'array'],
            'room_tags.*' => ['string', 'max:100'],

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
