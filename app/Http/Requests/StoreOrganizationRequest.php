<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * Default `is_active` to true when the client doesn't send it, so a
     * plain { name: "..." } request still creates an active organization
     * rather than relying on a database column default to carry that.
     */
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing([
            'is_active' => true,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:organizations,name'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter an organization name.',
            'name.unique' => 'An organization with this name already exists.',
        ];
    }
}