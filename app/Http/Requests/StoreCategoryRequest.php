<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated org member (Group Admin/Member) or Super Admin
        // (who must specify which org) may create a category.
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

            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('categories', 'name')->where(fn ($q) => $q->where('organization_id', $organizationId)),
            ],
        ];
    }
}
