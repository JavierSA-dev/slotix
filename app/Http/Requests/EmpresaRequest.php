<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasRole('SuperAdmin') ?? false;
    }

    public function rules(): array
    {
        $empresaId = $this->route('empresa');

        return [
            'nombre' => ['required', 'string', 'max:191'],
            'id' => ['sometimes', 'required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', Rule::unique('central.tenants', 'id')->ignore($empresaId)],
            'logo' => ['nullable', 'image', 'max:2048'],
            'tema' => ['nullable', 'string', 'in:neon,clasico,pastel'],
            'colores.primary' => ['nullable', 'string', 'max:7'],
            'colores.secondary' => ['nullable', 'string', 'max:7'],
            'colores.accent' => ['nullable', 'string', 'max:7'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'id.required' => 'El slug es obligatorio.',
            'id.unique' => 'Este slug ya está en uso.',
            'id.regex' => 'El slug solo puede contener letras minúsculas, números y guiones bajos.',
            'logo.image' => 'El logo debe ser una imagen.',
            'logo.max' => 'El logo no puede superar los 2MB.',
        ];
    }
}
