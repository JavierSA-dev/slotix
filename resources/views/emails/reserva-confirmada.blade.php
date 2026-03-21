<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 520px; margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background: #0d1a0d; padding: 24px 32px; }
        .header h1 { color: #c69444; margin: 0; font-size: 20px; }
        .header p { color: #9a8870; margin: 4px 0 0; font-size: 14px; }
        .body { padding: 28px 32px; }
        .greeting { font-size: 16px; color: #333; margin-bottom: 20px; }
        .detail-box { background: #f8f8f8; border-radius: 8px; padding: 16px 20px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #888; }
        .detail-value { font-weight: 600; color: #333; }
        .btn { display: inline-block; background: #c69444; color: #0d1a0d; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; margin-top: 20px; }
        .footer { padding: 16px 32px; background: #f0f0f0; font-size: 12px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $empresaNombre ?: config('app.name') }}</h1>
            <p>Confirmación de reserva</p>
        </div>
        <div class="body">
            <p class="greeting">Hola, <strong>{{ $reserva->nombre }}</strong> 👋</p>
            <p style="color:#555;font-size:14px;">Tu reserva ha sido confirmada. Aquí tienes los detalles:</p>

            <div class="detail-box">
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

            <p style="font-size:13px;color:#777;">Puedes gestionar o cancelar tu reserva desde el siguiente enlace:</p>
            <a href="{{ route('reservas.show', [$empresaSlug, $reserva->token]) }}" class="btn">Gestionar mi reserva</a>

            <p style="font-size:12px;color:#aaa;margin-top:20px;">¡Nos vemos pronto! ⛳</p>
        </div>
        <div class="footer">
            {{ $empresaNombre ?: config('app.name') }}
        </div>
    </div>
</body>
</html>
