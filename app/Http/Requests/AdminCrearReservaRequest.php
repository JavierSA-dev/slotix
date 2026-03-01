<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminCrearReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('hora_inicio')) {
            [$h, $m] = explode(':', $this->input('hora_inicio'));
            $this->merge(['hora_inicio' => (int) $h + ((int) $m / 60)]);
        }
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'numeric', 'min:0', 'max:23.99'],
            'num_personas' => ['required', 'integer', 'min:1', 'max:50'],
            'notas' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'num_personas.min' => 'Debe haber al menos 1 persona.',
        ];
    }
}
