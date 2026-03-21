<?php

namespace App\Http\Requests;

use App\Models\User;
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
            $this->merge(['hora_inicio' => (int) $h * 60 + (int) $m]);
        }

        if ($this->filled('user_id')) {
            $user = User::find($this->input('user_id'));
            if ($user) {
                $this->merge([
                    'nombre' => $user->name,
                    'email' => $user->email,
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'integer', 'min:0', 'max:1439'],
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
