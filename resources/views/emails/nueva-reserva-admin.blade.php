<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 520px; margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background: #1a2940; padding: 24px 32px; }
        .header h1 { color: #c69444; margin: 0; font-size: 20px; }
        .header p { color: #9aabb0; margin: 4px 0 0; font-size: 14px; }
        .body { padding: 28px 32px; }
        .detail-box { background: #f8f8f8; border-radius: 8px; padding: 16px 20px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #888; }
        .detail-value { font-weight: 600; color: #333; }
        .footer { padding: 16px 32px; background: #f0f0f0; font-size: 12px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $empresaNombre }}</h1>
            <p>Nueva reserva recibida</p>
        </div>
        <div class="body">
            <p style="color:#555;font-size:15px;">Se ha recibido una nueva reserva. Aquí tienes los detalles:</p>

            <div class="detail-box">
                <div class="detail-row">
                    <span class="detail-label">Cliente</span>
                    <span class="detail-value">{{ $reserva->nombre }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email</span>
                    <span class="detail-value">{{ $reserva->email }}</span>
                </div>
                @if($reserva->telefono)
                <div class="detail-row">
                    <span class="detail-label">Teléfono</span>
                    <span class="detail-value">{{ $reserva->telefono }}</span>
                </div>
                @endif
                <div class="detail-row">
                    <span class="detail-label">Fecha</span>
                    <span class="detail-value">{{ $reserva->fecha->format('d/m/Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Horario</span>
                    <span class="detail-value">{{ $horaFormateada }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Personas</span>
                    <span class="detail-value">{{ $reserva->num_personas }}</span>
                </div>
                @if($reserva->notas)
                <div class="detail-row">
                    <span class="detail-label">Notas</span>
                    <span class="detail-value">{{ $reserva->notas }}</span>
                </div>
                @endif
            </div>

            <p style="font-size:12px;color:#aaa;margin-top:20px;">Accede al panel de administración para gestionar esta reserva.</p>
        </div>
        <div class="footer">
            {{ $empresaNombre }} · Panel de administración
        </div>
    </div>
</body>
</html>
