<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Crear cuenta | Slotix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Crea tu cuenta para gestionar tus reservas fácilmente.">
    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/icons.min.css') }}">
    @vite(['resources/scss/custom/views/public-reservas.scss'])
</head>
<body class="mg-body d-flex align-items-center justify-content-center" style="min-height:100vh; padding:2rem 0;">

    <div class="w-100" style="max-width:420px; padding:1.5rem;">

        <div class="text-center mb-4">
            <a href="{{ route('login') }}" class="mg-logo-text" style="font-size:1.6rem;">
                Sloti<span>x</span>
            </a>
        </div>

        <div class="mg-reserva-card" style="margin:0; max-width:100%;">
            <div class="mg-reserva-titulo" style="font-size:1.1rem; margin-bottom:1.25rem;">
                <i class="bx bx-user-plus me-2"></i>Crear cuenta
            </div>

            <p style="font-size:.85rem; color:var(--mg-text-muted); margin-bottom:1.25rem;">
                Con una cuenta puedes consultar y cancelar tus reservas en cualquier momento sin necesidad del enlace por email.
            </p>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label" style="color:var(--mg-text-muted); font-size:.85rem;">
                        Nombre <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           id="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}"
                           placeholder="Tu nombre"
                           autofocus
                           style="background:var(--mg-dark-3); border-color:var(--mg-border); color:var(--mg-text);">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label" style="color:var(--mg-text-muted); font-size:.85rem;">
                        Email <span class="text-danger">*</span>
                    </label>
                    <input type="email"
                           name="email"
                           id="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}"
                           placeholder="tucorreo@ejemplo.com"
                           style="background:var(--mg-dark-3); border-color:var(--mg-border); color:var(--mg-text);">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label" style="color:var(--mg-text-muted); font-size:.85rem;">
                        Contraseña <span class="text-danger">*</span>
                    </label>
                    <input type="password"
                           name="password"
                           id="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Mínimo 8 caracteres"
                           style="background:var(--mg-dark-3); border-color:var(--mg-border); color:var(--mg-text);">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label" style="color:var(--mg-text-muted); font-size:.85rem;">
                        Repetir contraseña <span class="text-danger">*</span>
                    </label>
                    <input type="password"
                           name="password_confirmation"
                           id="password_confirmation"
                           class="form-control"
                           placeholder="Repite tu contraseña"
                           style="background:var(--mg-dark-3); border-color:var(--mg-border); color:var(--mg-text);">
                </div>

                <button type="submit" class="btn btn-mg-primary w-100">
                    Crear cuenta
                </button>
            </form>

            <hr style="border-color:var(--mg-border); margin:1.25rem 0;">

            <p class="text-center mb-0" style="font-size:.85rem; color:var(--mg-text-muted);">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" style="color:var(--mg-gold); text-decoration:none; font-weight:600;">Acceder</a>
            </p>
        </div>

        <p class="text-center mt-3" style="font-size:.8rem; color:var(--mg-text-muted);">
            <a href="{{ route('login') }}" style="color:var(--mg-text-muted); text-decoration:none;">
                <i class="bx bx-arrow-back me-1"></i>Volver a reservas
            </a>
        </p>
    </div>

</body>
</html>
