<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiaCerrado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDiasCerradosController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fecha_inicio' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'fecha_fin' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'motivo' => ['nullable', 'string', 'max:120'],
        ], [
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.after_or_equal' => 'No se pueden añadir fechas pasadas.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la de inicio.',
        ]);

        $dia = DiaCerrado::create($validated);

        return response()->json([
            'id' => $dia->id,
            'fecha_inicio_fmt' => $dia->fecha_inicio->format('d/m/Y'),
            'fecha_fin_fmt' => $dia->fecha_fin->format('d/m/Y'),
            'motivo' => $dia->motivo,
        ], 201);
    }

    public function destroy(int $diaCerrado): JsonResponse
    {
        DiaCerrado::findOrFail($diaCerrado)->delete();

        return response()->json(['message' => 'Período eliminado.']);
    }
}
