<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acceder | Minigolf Córdoba</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Accede a tu cuenta para gestionar tus reservas en Minigolf Córdoba.">
    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/icons.min.css') }}">
    @vite(['resources/scss/custom/views/public-reservas.scss'])
</head>
<body class="mg-body d-flex align-items-center justify-content-center" style="min-height:100vh;">

    <div class="w-100" style="max-width:420px; padding:1.5rem;">

        <div class="text-center mb-4">
            <a href="{{ route('reservas.public.index') }}" class="mg-logo-text" style="font-size:1.6rem;">
                Minigolf <span>Córdoba</span>
            </a>
        </div>

        <div class="mg-reserva-card" style="margin:0; max-width:100%;">
            <div class="mg-reserva-titulo" style="font-size:1.1rem; margin-bottom:1.25rem;">
                <i class="bx bx-log-in me-2"></i>Acceder a tu cuenta
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf

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
                           autocomplete="email"
                           autofocus
                           style="background:var(--mg-dark-3); border-color:var(--mg-border); color:var(--mg-text);">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label for="password" class="form-label mb-0" style="color:var(--mg-text-muted); font-size:.85rem;">
                            Contraseña <span class="text-danger">*</span>
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" style="font-size:.8rem; color:var(--mg-gold); text-decoration:none;">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif
                    </div>
                    <input type="password"
                           name="password"
                           id="password"
                           class="form-control mt-1 @error('password') is-invalid @enderror"
                           placeholder="Tu contraseña"
                           autocomplete="current-password"
                           style="background:var(--mg-dark-3); border-color:var(--mg-border); color:var(--mg-text);">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember" style="font-size:.85rem; color:var(--mg-text-muted);">
                        Recordarme
                    </label>
                </div>

                <button type="submit" class="btn btn-mg-primary w-100">
                    Entrar
                </button>
            </form>

            <hr style="border-color:var(--mg-border); margin:1.25rem 0;">

            <p class="text-center mb-0" style="font-size:.85rem; color:var(--mg-text-muted);">
                ¿No tienes cuenta?
                <a href="{{ route('register') }}" style="color:var(--mg-gold); text-decoration:none; font-weight:600;">Regístrate</a>
            </p>
        </div>

        <p class="text-center mt-3" style="font-size:.8rem; color:var(--mg-text-muted);">
            <a href="{{ route('reservas.public.index') }}" style="color:var(--mg-text-muted); text-decoration:none;">
                <i class="bx bx-arrow-back me-1"></i>Volver a reservas
            </a>
        </p>
    </div>

</body>
</html>
