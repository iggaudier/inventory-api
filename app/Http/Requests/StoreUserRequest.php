<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Only a Super Admin may create a user for an arbitrary organization
     * with an arbitrary role — route middleware already enforces
     * `role:super-admin`, but this stays explicit as a second layer.
     */
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'organization_id' => ['required', 'exists:organizations,id'],
            // Super Admin is intentionally not assignable here — there's
            // only ever meant to be a small, deliberately-created set of
            // those, not one handed out through a generic "add user" form.
            'role' => ['required', Rule::in(['group-admin', 'group-member'])],
        ];
    }
}