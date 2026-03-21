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
            'hora_apertura' => $this->timeToMinutos($this->input('hora_apertura', '00:00')),
            'hora_cierre' => $this->timeToMinutos($this->input('hora_cierre', '00:00')),
        ]);
    }

    public function rules(): array
    {
        return [
            'dias_semana' => ['required', 'array', 'min:1'],
            'dias_semana.*' => ['integer', 'between:0,6'],
            'hora_apertura' => ['required', 'integer', 'min:0', 'max:1380'],
            'hora_cierre' => ['required', 'integer', 'min:0', 'max:1440', 'gt:hora_apertura'],
            'duracion_tramo' => ['required', 'integer', 'in:15,30,45,60,90,120'],
            'aforo_por_tramo' => ['required', 'integer', 'min:1', 'max:100'],
            'horas_min_reserva' => ['required', 'integer', 'min:0', 'max:72'],
            'horas_min_cancelacion' => ['required', 'integer', 'min:0', 'max:72'],
            'semanas_max_reserva' => ['required', 'integer', 'min:1', 'max:52'],
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

    private function timeToMinutos(string $time): int
    {
        [$h, $m] = explode(':', $time);

        return (int) $h * 60 + (int) $m;
    }
}
