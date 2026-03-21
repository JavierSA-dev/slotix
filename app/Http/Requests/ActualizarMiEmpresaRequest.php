<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarMiEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasAnyRole(['SuperAdmin', 'Admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:191'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'tema' => ['nullable', 'string', 'in:neon,clasico,pastel'],
            'colores.primary' => ['nullable', 'string', 'max:7'],
            'colores.secondary' => ['nullable', 'string', 'max:7'],
            'colores.accent' => ['nullable', 'string', 'max:7'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'logo.image' => 'El logo debe ser una imagen.',
            'logo.max' => 'El logo no puede superar los 2MB.',
        ];
    }
}
