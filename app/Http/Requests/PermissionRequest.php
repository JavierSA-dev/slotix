<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
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
        $permissionId = $this->route('permission')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->ignore($permissionId)],
            'roles' => 'nullable|array',
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe un permiso con ese nombre.',
            'roles.*.exists' => 'Uno de los roles seleccionados no es válido.',
        ];
    }
}
