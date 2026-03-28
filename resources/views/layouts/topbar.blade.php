<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <!-- LOGO -->
            <div class="navbar-brand-box">
                <a href="{{ route('admin.dashboard') }}" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="{{ URL::asset('build/images/logo.svg') }}" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ URL::asset('build/images/logo-dark.png') }}" alt="" height="17">
                    </span>
                </a>
                <a href="{{ route('admin.dashboard') }}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ URL::asset('build/images/logo-light.png') }}" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ URL::asset('build/images/logo-light.png') }}" alt="" height="25">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>
        </div>

        <div class="d-flex">
            {{-- SELECTOR DE EMPRESA (SuperAdmin y Admin con múltiples empresas) --}}
            @hasanyrole('SuperAdmin|Admin')
            @if(isset($empresasDisponibles) && $empresasDisponibles->count() > 1)
            <div class="d-flex align-items-center px-2">
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn btn-sm header-item waves-effect d-flex align-items-center gap-2"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                        title="Cambiar empresa">
                        <i class="bx bx-buildings font-size-16"></i>
                        <span class="d-none d-xl-inline-block font-size-13">{{ $empresaActual?->nombre ?? 'Sin empresa' }}</span>
                        <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" style="min-width:200px;">
                        <h6 class="dropdown-header">Seleccionar empresa</h6>
                        @foreach($empresasDisponibles as $emp)
                        <a class="dropdown-item empresa-switch-item {{ $empresaActual && $empresaActual->id === $emp->id ? 'active' : '' }}"
                           href="javascript:void(0)" data-empresa-id="{{ $emp->id }}">
                            <i class="bx bx-buildings me-1"></i>
                            {{ $emp->nombre }}
                            @if($empresaActual && $empresaActual->id === $emp->id)
                                <i class="bx bx-check ms-1 text-success"></i>
                            @endif
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
            @elseif(isset($empresaActual) && $empresaActual)
            <div class="d-flex align-items-center px-2">
                <span class="d-none d-xl-inline-block font-size-13 text-muted">
                    <i class="bx bx-buildings me-1"></i>{{ $empresaActual->nombre }}
                </span>
            </div>
            @endif
            @endhasanyrole

            {{-- TOGGLE - Mantenimiento (solo admins) --}}
            @hasanyrole('SuperAdmin|Admin')
            <div class="d-flex align-items-center px-2" id="mantenimiento-toggle-wrapper"
                 title="{{ $enMantenimiento ? 'Desactivar modo mantenimiento' : 'Activar modo mantenimiento' }}">
                <span class="d-none d-xl-inline-block me-2 font-size-13 text-{{ $enMantenimiento ? 'warning' : 'muted' }}" id="mantenimiento-label">
                    Mantenimiento
                </span>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input"
                           type="checkbox"
                           role="switch"
                           id="switch-mantenimiento"
                           style="width:2.2em; height:1.2em; cursor:pointer;"
                           {{ $enMantenimiento ? 'checked' : '' }}>
                </div>
            </div>
            @endhasanyrole

            {{-- BOTÓN COPIAR URL PÚBLICA DE RESERVAS --}}
            @hasanyrole('SuperAdmin|Admin')
            @if(session('empresa_id'))
            <div class="d-flex align-items-center px-1">
                <button type="button"
                        id="btn-copiar-url-reservas"
                        class="btn header-item waves-effect"
                        title="Copiar enlace público de reservas"
                        data-url="{{ route('reservas.public.index', session('empresa_id')) }}">
                    <i class="bx bx-link-alt font-size-18" id="icon-copiar-url"></i>
                </button>
            </div>
            @endif
            @endhasanyrole

            {{-- CAMPANITA DE NOTIFICACIONES (solo admins) --}}
            @hasanyrole('SuperAdmin|Admin')
            <div class="dropdown d-inline-block" id="notif-dropdown">
                <button type="button"
                        class="btn header-item noti-icon waves-effect"
                        id="btn-notificaciones"
                        data-bs-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                        data-url="{{ route('admin.notificaciones.index') }}"
                        data-leida-url="{{ url('admin/notificaciones') }}"
                        title="Notificaciones">
                    <i class="bx bx-bell"></i>
                    <span class="badge bg-danger rounded-pill d-none" id="notif-badge">0</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0" style="width: 330px; max-height: 420px; overflow-y: auto;" id="notif-panel">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-semibold font-size-14">Notificaciones</span>
                        <a href="javascript:void(0)" id="btn-marcar-todas-leidas" class="font-size-12 text-muted">Marcar todas como leídas</a>
                    </div>
                    <div id="notif-lista">
                        <div class="text-center text-muted py-3 font-size-13">Cargando...</div>
                    </div>
                </div>
            </div>
            @endhasanyrole

            {{-- DROPDOWN - Usuario --}}
            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img class="rounded-circle header-profile-user"
                        src="{{ isset(Auth::user()->avatar) ? URL::asset('storage/avatares/'.Auth::user()->avatar) : asset('build/images/users/avatar.png') }}"
                        alt="Avatar">
                    <span class="d-none d-xl-inline-block ms-1">{{ ucfirst(Auth::user()->name) }}</span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item d-block" href="javascript:void(0)" data-bs-toggle="modal"
                        data-bs-target=".change-password">
                        <i class="bx bx-lock font-size-16 align-middle me-1"></i> Cambiar contraseña
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="javascript:void();"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i> Cerrar sesión
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.empresa-switch-item').forEach(function (el) {
        el.addEventListener('click', function () {
            var empresaId = this.dataset.empresaId;
            fetch('{{ route('admin.switch.empresa') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ empresa_id: empresaId })
            }).then(function () {
                window.location.reload();
            });
        });
    });

    var btnCopiar = document.getElementById('btn-copiar-url-reservas');
    if (btnCopiar) {
        btnCopiar.addEventListener('click', function () {
            var url = this.dataset.url;
            var icon = document.getElementById('icon-copiar-url');
            navigator.clipboard.writeText(url).then(function () {
                icon.classList.replace('bx-link-alt', 'bx-check');
                btnCopiar.title = '¡Enlace copiado!';
                setTimeout(function () {
                    icon.classList.replace('bx-check', 'bx-link-alt');
                    btnCopiar.title = 'Copiar enlace público de reservas';
                }, 2000);
            });
        });
    }
});
</script>

<div class="modal fade change-password" tabindex="-1" role="dialog" aria-labelledby="changePasswordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordLabel">Cambiar contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="change-password">
                    @csrf
                    <input type="hidden" value="{{ Auth::user()->id }}" id="data_id">
                    <div class="mb-3">
                        <label for="current-password">Contraseña actual <span class="text-danger">*</span></label>
                        <input id="current-password" type="password" class="form-control" name="current_password" placeholder="Introduce tu contraseña actual">
                        <div class="text-danger" id="current_passwordError" data-ajax-feedback="current_password"></div>
                    </div>
                    <div class="mb-3">
                        <label for="password">Nueva contraseña <span class="text-danger">*</span></label>
                        <input id="password" type="password" class="form-control" name="password" placeholder="Nueva contraseña">
                        <div class="text-danger" id="passwordError" data-ajax-feedback="password"></div>
                    </div>
                    <div class="mb-3">
                        <label for="password-confirm">Confirmar contraseña <span class="text-danger">*</span></label>
                        <input id="password-confirm" type="password" class="form-control" name="password_confirmation" placeholder="Repite la nueva contraseña">
                        <div class="text-danger" id="password_confirmError" data-ajax-feedback="password-confirm"></div>
                    </div>
                    <div class="mt-3 d-grid">
                        <button class="btn btn-primary waves-effect waves-light UpdatePassword" data-id="{{ Auth::user()->id }}" type="submit">Actualizar contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
