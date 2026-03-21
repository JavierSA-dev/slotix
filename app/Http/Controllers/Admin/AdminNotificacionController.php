<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificacionService;
use Illuminate\Http\JsonResponse;

class AdminNotificacionController extends Controller
{
    public function __construct(protected NotificacionService $notificacionService) {}

    public function index(): JsonResponse
    {
        $userId = auth()->id();
        $empresaSlug = session('empresa_id', '');
        $resultado = $this->notificacionService->getNoLeidas($userId, $empresaSlug);

        return response()->json([
            'total' => $resultado['total'],
            'notificaciones' => $resultado['notificaciones']->map(fn ($n) => [
                'id' => $n->id,
                'tipo' => $n->tipo,
                'datos' => $n->datos,
                'created_at' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function marcarLeida(int $id): JsonResponse
    {
        $this->notificacionService->marcarLeida($id, auth()->id());

        return response()->json(['ok' => true]);
    }

    public function marcarTodasLeidas(): JsonResponse
    {
        $this->notificacionService->marcarTodasLeidas(auth()->id(), session('empresa_id', ''));

        return response()->json(['ok' => true]);
    }
}
