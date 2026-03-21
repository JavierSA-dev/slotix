<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo expirada</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #120d02;
            color: #e8dcc8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #1a1306;
            border: 1px solid #2d2410;
            border-radius: 16px;
            padding: 48px 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        .icon {
            font-size: 56px;
            margin-bottom: 16px;
            display: block;
        }
        h1 {
            font-size: 1.6rem;
            color: #c19849;
            margin-bottom: 12px;
        }
        p {
            color: #9a8870;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .cta {
            display: inline-block;
            background: #c19849;
            color: #0d0901;
            font-weight: 700;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.95rem;
            transition: background .2s;
        }
        .cta:hover { background: #d4a955; }
        .divider { border: none; border-top: 1px solid #2d2410; margin: 28px 0; }
        .small { font-size: .8rem; color: #535353; }
    </style>
</head>
<body>
    <div class="card">
        <span class="icon">⏳</span>
        <h1>Tu periodo de prueba ha finalizado</h1>
        <p>
            El enlace de demo que has utilizado ya no está activo.<br>
            Si quieres conocer más sobre esta plataforma, contáctanos y te preparamos una nueva demo personalizada.
        </p>
        <a href="mailto:{{ config('mail.from.address') }}" class="cta">Solicitar nueva demo</a>
        <hr class="divider">
        <p class="small">Sistema de reservas online · Powered by {{ config('app.name') }}</p>
    </div>
</body>
</html>
