<?php

namespace App\Http\Requests;

use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Brand $brand */
        $brand = $this->route('brand');

        return $this->user()?->can('update', $brand) ?? false;
    }

    public function rules(): array
    {
        /** @var Brand $brand */
        $brand = $this->route('brand');

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('brands', 'name')
                    ->where(fn ($query) => $query->where('organization_id', $brand->organization_id))
                    ->ignore($brand),
            ],
        ];
    }
}
