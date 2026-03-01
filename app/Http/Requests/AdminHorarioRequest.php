<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminHorarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'hora_apertura' => $this->timeToDecimal($this->input('hora_apertura', '00:00')),
            'hora_cierre' => $this->timeToDecimal($this->input('hora_cierre', '00:00')),
        ]);
    }

    public function rules(): array
    {
        return [
            'dias_semana' => ['required', 'array', 'min:1'],
            'dias_semana.*' => ['integer', 'between:0,6'],
            'hora_apertura' => ['required', 'numeric', 'min:0', 'max:23'],
            'hora_cierre' => ['required', 'numeric', 'min:0', 'max:24', 'gt:hora_apertura'],
            'duracion_tramo' => ['required', 'integer', 'in:15,30,45,60,90,120'],
            'aforo_por_tramo' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'dias_semana.required' => 'Debes seleccionar al menos un día.',
            'hora_cierre.gt' => 'La hora de cierre debe ser posterior a la de apertura.',
            'duracion_tramo.in' => 'La duración del tramo no es válida.',
            'aforo_por_tramo.min' => 'El aforo mínimo es 1 persona.',
        ];
    }

    private function timeToDecimal(string $time): float
    {
        [$h, $m] = explode(':', $time);

        return (int) $h + ((int) $m / 60);
    }
}
