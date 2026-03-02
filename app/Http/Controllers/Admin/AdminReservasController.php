<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\ReservaDataTableConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCrearReservaRequest;
use App\Mail\ReservaConfirmadaMail;
use App\Models\Reserva;
use App\Models\User;
use App\Services\ReservaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AdminReservasController extends Controller
{
    public function __construct(protected ReservaService $reservaService) {}

    public function index(): View
    {
        $config = new ReservaDataTableConfig;
        $usuarios = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['SuperAdmin', 'Admin']))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.reservas.index', compact('config', 'usuarios'));
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

    public function confirmar(Reserva $reserva): JsonResponse
    {
        if ($reserva->estado !== 'pendiente') {
            return response()->json(['message' => 'Solo se pueden confirmar reservas pendientes.'], 422);
        }

        $reserva->update(['estado' => 'confirmada']);
        $horaFormateada = $this->reservaService->decimalAHora((float) $reserva->hora_inicio);
        Mail::to($reserva->email)->send(new ReservaConfirmadaMail($reserva, $horaFormateada));

        return response()->json(['message' => 'Reserva confirmada.']);
    }

    public function cancelarAdmin(Reserva $reserva): JsonResponse
    {
        if ($reserva->estado === 'cancelada') {
            return response()->json(['message' => 'La reserva ya está cancelada.'], 422);
        }

        $this->reservaService->cancelarReserva($reserva);

        return response()->json(['message' => 'Reserva cancelada.']);
    }

    public function storeAdmin(AdminCrearReservaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $reserva = $this->reservaService->crearReserva($data);
        $horaFormateada = $this->reservaService->decimalAHora((float) $reserva->hora_inicio);
        Mail::to($reserva->email)->send(new ReservaConfirmadaMail($reserva, $horaFormateada));

        return response()->json(['message' => 'Reserva creada correctamente.', 'id' => $reserva->id]);
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        $start = $request->input('start', today()->format('Y-m-d'));
        $end = $request->input('end', today()->addMonth()->format('Y-m-d'));

        $reservas = Reserva::whereBetween('fecha', [
            substr($start, 0, 10),
            substr($end, 0, 10),
        ])
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->get();

        $events = $reservas->map(function ($r) {
            $horaInicio = $this->reservaService->decimalAHora((float) $r->hora_inicio);
            $horaFin = $this->reservaService->decimalAHora((float) $r->hora_fin);

            return [
                'id' => $r->id,
                'title' => $r->nombre.' ('.$r->num_personas.'p)',
                'start' => $r->fecha->format('Y-m-d').'T'.$horaInicio.':00',
                'end' => $r->fecha->format('Y-m-d').'T'.$horaFin.':00',
                'backgroundColor' => $r->estado === 'confirmada' ? '#2a5228' : '#856404',
                'borderColor' => $r->estado === 'confirmada' ? '#3a7038' : '#c19849',
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'estado' => $r->estado,
                    'email' => $r->email,
                    'telefono' => $r->telefono,
                    'personas' => $r->num_personas,
                    'notas' => $r->notas,
                    'token' => $r->token,
                    'fecha_fmt' => $r->fecha->format('d/m/Y'),
                    'hora_inicio_fmt' => $horaInicio,
                    'hora_fin_fmt' => $horaFin,
                ],
            ];
        });

        return response()->json($events);
    }

    public function buscarUsuarios(Request $request): JsonResponse
    {
        $q = $request->input('q', '');
        $usuarios = User::query()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($usuarios);
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
        $urlVer = route('reservas.show', $reserva->token);
        $btn = '<div class="d-flex gap-1 justify-content-center">';
        $btn .= '<a href="'.$urlVer.'" target="_blank" class="btn btn-sm btn-info" title="Ver reserva"><i class="fa fa-eye"></i></a>';

        if ($reserva->estado === 'pendiente') {
            $btn .= '<button class="btn btn-sm btn-success btn-confirmar-reserva" data-id="'.$reserva->id.'" title="Confirmar"><i class="fa fa-check"></i></button>';
        }

        if ($reserva->estado !== 'cancelada') {
            $btn .= '<button class="btn btn-sm btn-danger btn-cancelar-reserva" data-id="'.$reserva->id.'" title="Cancelar"><i class="fa fa-times"></i></button>';
        }

        $btn .= '</div>';

        return $btn;
    }
}
