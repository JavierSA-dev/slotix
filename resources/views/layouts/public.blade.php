<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Reservas') | Minigolf Córdoba</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta-description', 'Reserva online tu partida de minigolf en Córdoba, Andalucía. Elige fecha, horario y disfruta en familia.')">
    <meta name="keywords" content="minigolf Córdoba, reservas minigolf, ocio Córdoba, minigolf familiar">
    <meta name="theme-color" content="#0d1a0d">
    <meta property="og:title" content="@yield('title', 'Reservas') | Minigolf Córdoba">
    <meta property="og:description" content="@yield('meta-description', 'Reserva online tu partida de minigolf en Córdoba, Andalucía.')">
    <meta property="og:type" content="website">
    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/css/icons.min.css') }}">
    @vite(['resources/scss/custom/views/public-reservas.scss'])
    @stack('styles')
</head>
<body class="mg-body">

    <header class="mg-header">
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('reservas.public.index') }}" class="mg-logo-text">
                Minigolf <span>Córdoba</span>
            </a>
            <nav class="mg-nav-links d-flex align-items-center gap-3">
                @guest
                    <a href="{{ route('login') }}">Acceder</a>
                @endguest
                @auth
                    <a href="{{ route('mis-reservas.index') }}">Mis reservas</a>
                    @hasanyrole('SuperAdmin|Admin')
                        <a href="{{ route('admin.dashboard') }}">Panel admin</a>
                    @endhasanyrole
                    <a href="javascript:void(0);" onclick="event.preventDefault(); document.getElementById('logout-pub').submit();">Salir</a>
                    <form id="logout-pub" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mg-main">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @yield('content')
    </main>

    <script src="{{ URL::asset('build/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
</body>
</html>
