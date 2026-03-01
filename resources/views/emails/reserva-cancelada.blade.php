<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 520px; margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background: #2a2a2a; padding: 24px 32px; }
        .header h1 { color: #c69444; margin: 0; font-size: 20px; }
        .header p { color: #888; margin: 4px 0 0; font-size: 14px; }
        .body { padding: 28px 32px; }
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
            <h1>Minigolf Córdoba</h1>
            <p>Cancelación de reserva</p>
        </div>
        <div class="body">
            <p style="font-size:16px;color:#333;">Hola, <strong>{{ $reserva->nombre }}</strong></p>
            <p style="color:#555;font-size:14px;">Tu reserva ha sido cancelada. Aquí tienes los detalles de la reserva cancelada:</p>

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
            </div>

            <p style="font-size:13px;color:#777;">Si quieres hacer una nueva reserva, puedes hacerlo aquí:</p>
            <a href="{{ route('reservas.public.index') }}" class="btn">Hacer nueva reserva</a>
        </div>
        <div class="footer">
            Minigolf Córdoba · Córdoba, Andalucía
        </div>
    </div>
</body>
</html>
