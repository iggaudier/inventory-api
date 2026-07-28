<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isGroupAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            // organization_id is NOT accepted from input; it is forced
            // to the Group Admin's own organization in the controller.
        ];
    }
}
