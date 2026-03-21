<?php

namespace App\Services;

use App\Mail\NuevaReservaAdminMail;
use App\Models\AdminNotificacion;
use App\Models\Empresa;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class NotificacionService
{
    /**
     * Notifica a los admins de la empresa al crearse una reserva.
     * Crea notificación en BD y envía email.
     */
    public function nuevaReserva(Reserva $reserva, string $empresaSlug, string $horaFormateada): void
    {
        $empresa = Empresa::on('central')->find($empresaSlug);
        if (! $empresa) {
            return;
        }

        $admins = $this->getAdminsEmpresa($empresaSlug);

        foreach ($admins as $admin) {
            AdminNotificacion::create([
                'user_id' => $admin->id,
                'empresa_id' => $empresaSlug,
                'tipo' => 'nueva_reserva',
                'datos' => $this->datosReserva($reserva, $horaFormateada),
            ]);

            Mail::to($admin->email)->send(
                new NuevaReservaAdminMail($reserva, $horaFormateada, $empresa->nombre)
            );
        }
    }

    /**
     * Notifica a los admins cuando se cancela una reserva.
     */
    public function reservaCancelada(Reserva $reserva, string $empresaSlug, string $horaFormateada): void
    {
        $admins = $this->getAdminsEmpresa($empresaSlug);

        foreach ($admins as $admin) {
            AdminNotificacion::create([
                'user_id' => $admin->id,
                'empresa_id' => $empresaSlug,
                'tipo' => 'cancelacion',
                'datos' => $this->datosReserva($reserva, $horaFormateada),
            ]);
        }
    }

    /**
     * Notifica a los admins cuando se cambia la fecha de una reserva.
     */
    public function reservaCambioFecha(Reserva $reserva, string $empresaSlug, string $horaFormateada, string $fechaAnterior): void
    {
        $admins = $this->getAdminsEmpresa($empresaSlug);
        $datos = $this->datosReserva($reserva, $horaFormateada);
        $datos['fecha_anterior'] = $fechaAnterior;

        foreach ($admins as $admin) {
            AdminNotificacion::create([
                'user_id' => $admin->id,
                'empresa_id' => $empresaSlug,
                'tipo' => 'cambio_fecha',
                'datos' => $datos,
            ]);
        }
    }

    /**
     * Marca una notificación como leída.
     */
    public function marcarLeida(int $id, int $userId): void
    {
        AdminNotificacion::where('id', $id)
            ->where('user_id', $userId)
            ->update(['leida' => true]);
    }

    /**
     * Marca todas las notificaciones de un usuario como leídas.
     */
    public function marcarTodasLeidas(int $userId, string $empresaSlug): void
    {
        AdminNotificacion::where('user_id', $userId)
            ->where('empresa_id', $empresaSlug)
            ->where('leida', false)
            ->update(['leida' => true]);
    }

    /**
     * Devuelve las últimas notificaciones no leídas de un usuario.
     *
     * @return array{total: int, notificaciones: Collection}
     */
    public function getNoLeidas(int $userId, string $empresaSlug): array
    {
        $notificaciones = AdminNotificacion::where('user_id', $userId)
            ->where('empresa_id', $empresaSlug)
            ->where('leida', false)
            ->latest()
            ->limit(20)
            ->get();

        return [
            'total' => $notificaciones->count(),
            'notificaciones' => $notificaciones,
        ];
    }

    /**
     * Devuelve usuarios Admin/SuperAdmin de una empresa.
     *
     * @return Collection<int, User>
     */
    private function getAdminsEmpresa(string $empresaSlug): Collection
    {
        return User::on('central')
            ->role(['Admin', 'SuperAdmin'])
            ->whereHas('empresas', fn ($q) => $q->where('tenants.id', $empresaSlug))
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function datosReserva(Reserva $reserva, string $horaFormateada): array
    {
        return [
            'reserva_id' => $reserva->id,
            'nombre' => $reserva->nombre,
            'email' => $reserva->email,
            'fecha' => $reserva->fecha->format('d/m/Y'),
            'hora' => $horaFormateada,
            'personas' => $reserva->num_personas,
            'token' => $reserva->token,
            'estado' => $reserva->estado,
        ];
    }
}
