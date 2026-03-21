<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminActualizarReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('hora_inicio') && str_contains($this->input('hora_inicio'), ':')) {
            [$h, $m] = explode(':', $this->input('hora_inicio'));
            $this->merge(['hora_inicio' => (int) $h + ((int) $m / 60)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'fecha' => ['required', 'date'],
            'hora_inicio' => ['required', 'numeric', 'min:0', 'max:23.99'],
            'num_personas' => ['required', 'integer', 'min:1'],
            'notas' => ['nullable', 'string', 'max:500'],
            'estado' => ['required', Rule::in(['pendiente', 'confirmada', 'cancelada'])],
        ];
    }
}
