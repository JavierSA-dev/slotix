<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemoInvitacion;
use App\Models\Empresa;
use App\Models\HorarioConfig;
use App\Models\Modulo;
use App\Models\Reserva;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DemoController extends Controller
{
    public function index(): View
    {
        $demos = DemoInvitacion::with('creadoPor')->orderByDesc('created_at')->get();

        return view('admin.demos.index', compact('demos'));
    }

    public function store(): JsonResponse
    {
        $tema = request()->input('tema', 'neon');

        if (! in_array($tema, array_keys(config('temas')))) {
            $tema = 'neon';
        }

        $tenantId = 'demo_'.bin2hex(random_bytes(5));

        $empresa = Empresa::create([
            'id' => $tenantId,
            'nombre' => 'Demo',
            'tema' => $tema,
            'activo' => true,
        ]);

        $moduloReservas = Modulo::where('nombre', 'reservas')->first();
        if ($moduloReservas) {
            $empresa->modulos()->attach($moduloReservas->id, ['activo' => true]);
        }

        tenancy()->initialize($empresa);

        Artisan::call('migrate', [
            '--path' => 'database/migrations/tenant',
            '--force' => true,
            '--realpath' => false,
        ]);

        $this->sembrarDatos();

        tenancy()->end();

        $adminDemo = User::create([
            'name' => 'Admin Demo',
            'email' => "admin_{$tenantId}@demo.slotix.app",
            'password' => Hash::make(Str::random(32)),
            'activo' => true,
            'email_verified_at' => now(),
        ]);
        $adminDemo->assignRole('Admin');
        $empresa->users()->attach($adminDemo->id);

        User::create([
            'name' => 'Cliente Demo',
            'email' => "user_{$tenantId}@demo.slotix.app",
            'password' => Hash::make(Str::random(32)),
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        $invitacion = DemoInvitacion::create([
            'tenant_id' => $tenantId,
            'creada_por' => auth()->id(),
            'expira_en' => now()->addDays(7),
        ]);

        return response()->json([
            'message' => 'Demo creada correctamente.',
            'url' => route('reservas.public.index', $tenantId),
            'tenant_id' => $tenantId,
            'expira_en' => $invitacion->expira_en->format('d/m/Y'),
        ]);
    }

    public function destroy(string $tenantId): JsonResponse
    {
        $empresa = Empresa::find($tenantId);

        if ($empresa) {
            $empresa->delete();
        }

        User::whereIn('email', [
            "admin_{$tenantId}@demo.slotix.app",
            "user_{$tenantId}@demo.slotix.app",
        ])->delete();

        DemoInvitacion::where('tenant_id', $tenantId)->delete();

        return response()->json(['message' => 'Demo eliminada.']);
    }

    private function sembrarDatos(): void
    {
        HorarioConfig::create([
            'dias_semana' => [1, 2, 3, 4, 5, 6, 7],
            'hora_apertura' => 10.00,
            'hora_cierre' => 22.00,
            'duracion_tramo' => 1.00,
            'aforo_por_tramo' => 3,
            'horas_min_reserva' => 2,
            'horas_min_cancelacion' => 24,
            'semanas_max_reserva' => 4,
            'activo' => true,
            'en_mantenimiento' => false,
        ]);

        $nombres = [
            'Carlos García', 'María López', 'Juan Martínez', 'Ana Sánchez',
            'Pedro Fernández', 'Laura Gómez', 'Miguel Torres', 'Isabel Ruiz',
            'David Díaz', 'Carmen Morales', 'Javier Jiménez', 'Rosa Álvarez',
            'Fernando Castro', 'Elena Romero', 'Pablo Vargas',
        ];

        $estados = ['confirmada', 'confirmada', 'confirmada', 'pendiente', 'cancelada'];
        $horas = [10.00, 11.00, 12.00, 13.00, 15.00, 16.00, 17.00, 18.00, 19.00, 20.00];
        $personas = [2, 4, 1, 3, 2, 4, 2, 3, 1, 2, 4, 3, 2, 1, 3];

        foreach ($nombres as $i => $nombre) {
            $fecha = Carbon::today()->addDays($i - 3)->format('Y-m-d');
            $hora = $horas[$i % count($horas)];

            Reserva::create([
                'nombre' => $nombre,
                'email' => Str::lower(Str::slug(explode(' ', $nombre)[0])).'@ejemplo.com',
                'fecha' => $fecha,
                'hora_inicio' => $hora,
                'hora_fin' => $hora + 1.00,
                'num_personas' => $personas[$i],
                'token' => Str::uuid(),
                'estado' => $estados[$i % count($estados)],
            ]);
        }
    }
}
