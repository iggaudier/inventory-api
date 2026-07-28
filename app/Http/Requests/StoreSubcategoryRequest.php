<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubcategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
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

            'category_id' => [
                'required', 'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('organization_id', $organizationId)),
            ],

            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('subcategories', 'name')->where(
                    fn ($q) => $q->where('organization_id', $organizationId)
                        ->where('category_id', $this->input('category_id'))
                ),
            ],
        ];
    }
}
