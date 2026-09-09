<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBrandRequest extends FormRequest
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
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('brands', 'name')->where(
                    fn ($query) => $query->where('organization_id', $organizationId)
                ),
            ],
        ];
    }
}
