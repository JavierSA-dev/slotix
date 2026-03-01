<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Preparar los datos antes de la validación.
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'role' => $this->input('role', []),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isCreating = $this->isMethod('POST') && ! $this->route('user');

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'avatar' => 'nullable|image|mimes:jpeg,png,gif,jpg,webp|max:2048',
            'activo' => 'nullable|boolean',

            // Contraseña: requerida en creación, opcional en edición
            'newpassword' => $isCreating
                ? 'required|string|min:8|confirmed'
                : 'nullable|string|min:8|confirmed',

            // Validación de roles
            'role' => 'nullable|array',
            'role.*' => [
                'string',
                Rule::exists('roles', 'name'),
                function (string $attribute, mixed $value, Closure $fail) {
                    $user = auth()->user();
                    if ($user && ! $user->canAssignRole($value)) {
                        $fail(__('validation.role_hierarchy', ['role' => $value]));
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'newpassword.required' => 'La contraseña es requerida.',
            'newpassword.min' => 'La contraseña debe tener al menos :min caracteres.',
            'newpassword.confirmed' => 'Las contraseñas no coinciden.',
            'email.unique' => 'Este email ya está registrado.',
            'role.*.exists' => 'Uno de los roles seleccionados no existe.',
        ];
    }
}
