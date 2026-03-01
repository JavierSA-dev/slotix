<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($roleId)],
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe un rol con ese nombre.',
            'permissions.*.exists' => 'Uno de los permisos seleccionados no es válido.',
        ];
    }
}
