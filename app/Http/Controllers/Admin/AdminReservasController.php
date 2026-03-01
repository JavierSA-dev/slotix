<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\ReservaDataTableConfig;
use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Services\ReservaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AdminReservasController extends Controller
{
    public function __construct(protected ReservaService $reservaService) {}

    public function index(): View
    {
        $config = new ReservaDataTableConfig;

        return view('admin.reservas.index', compact('config'));
    }

    public function getAjax(Request $request): JsonResponse
    {
        $query = Reserva::query()->select([
            'id', 'nombre', 'email', 'telefono',
            'fecha', 'hora_inicio', 'hora_fin',
            'num_personas', 'estado', 'token', 'created_at',
        ]);

        $this->applyFilters($query, $request);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('fecha_fmt', fn ($r) => $r->fecha->format('d/m/Y'))
            ->addColumn('hora_fmt', fn ($r) => $this->reservaService->decimalAHora((float) $r->hora_inicio)
                .' - '.$this->reservaService->decimalAHora((float) $r->hora_fin))
            ->addColumn('estado_badge', fn ($r) => $this->renderEstadoBadge($r->estado))
            ->addColumn('action', fn ($r) => $this->renderAcciones($r))
            ->rawColumns(['estado_badge', 'action'])
            ->make(true);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->input('fecha'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        if ($request->filled('search') && $search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
    }

    private function renderEstadoBadge(string $estado): string
    {
        return match ($estado) {
            'confirmada' => '<span class="pill-label pill-label-primary">Confirmada</span>',
            'cancelada' => '<span class="pill-label pill-label-secondary">Cancelada</span>',
            default => '<span class="pill-label pill-label-warning">Pendiente</span>',
        };
    }

    private function renderAcciones(Reserva $reserva): string
    {
        $url = route('reservas.show', $reserva->token);

        return '<a href="'.$url.'" target="_blank" class="btn btn-sm btn-info" title="Ver reserva">
                    <i class="fa fa-eye"></i>
                </a>';
    }
}
