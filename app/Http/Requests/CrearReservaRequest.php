<?php

namespace App\Http\Requests;

use App\Models\HorarioConfig;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class CrearReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'fecha' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.$this->fechaMaxima()],
            'hora_inicio' => ['required', 'integer', 'min:0', 'max:1439'],
            'num_personas' => ['required', 'integer', 'min:1', 'max:20'],
            'notas' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function fechaMaxima(): string
    {
        $horario = HorarioConfig::where('activo', true)->first();
        $semanas = $horario ? (int) $horario->semanas_max_reserva : 4;

        return Carbon::today()->addWeeks($semanas)->format('Y-m-d');
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'Introduce un email válido.',
            'fecha.required' => 'Debes seleccionar una fecha.',
            'fecha.after_or_equal' => 'No se pueden hacer reservas en fechas pasadas.',
            'fecha.before_or_equal' => 'No se pueden hacer reservas con tanta antelación.',
            'hora_inicio.required' => 'Debes seleccionar una franja horaria.',
            'num_personas.required' => 'Indica el número de personas.',
            'num_personas.min' => 'Debe reservar para al menos 1 persona.',
            'num_personas.max' => 'El máximo por reserva es 20 personas.',
        ];
    }
}
