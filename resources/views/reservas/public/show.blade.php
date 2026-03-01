@extends('layouts.public')

@section('title', 'Mi reserva')

@push('styles')
<style>
@media print {
    .mg-header, .mg-nav-links, .no-print, nav { display: none !important; }
    .mg-body { background: #fff !important; color: #000 !important; }
    .mg-reserva-card {
        border: 1px solid #ccc !important;
        box-shadow: none !important;
        color: #000 !important;
        max-width: 100% !important;
    }
    .mg-reserva-titulo, .campo-valor { color: #000 !important; }
    .campo-label { color: #555 !important; }
    .mg-estado { border: 1px solid #999 !important; }
}
</style>
@endpush

@section('content')
    {{-- Caja principal de detalle --}}
    <div class="mg-reserva-card" id="reserva-print-area">
        <div class="d-flex justify-content-between align-items-start mb-0" style="margin-bottom:1.5rem !important;">
            <div class="mg-reserva-titulo mb-0" style="margin-bottom:0;">
                <i class="bx bx-calendar-check me-2"></i>Detalle de tu reserva
            </div>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print" style="border-color:var(--mg-border); color:var(--mg-text-muted); font-size:.8rem;">
                <i class="bx bx-download me-1"></i>Guardar PDF
            </button>
        </div>

        <div class="mg-reserva-campo">
            <span class="campo-label">Estado</span>
            <span class="campo-valor mg-estado {{ $reserva->estado }}">
                {{ ucfirst($reserva->estado) }}
            </span>
        </div>

        <div class="mg-reserva-campo">
            <span class="campo-label">Fecha</span>
            <span class="campo-valor">{{ $reserva->fecha->format('d/m/Y') }}</span>
        </div>

        <div class="mg-reserva-campo">
            <span class="campo-label">Horario</span>
            <span class="campo-valor">{{ $horaFormateada }} – {{ $horaFinFormateada }}</span>
        </div>

        <div class="mg-reserva-campo">
            <span class="campo-label">Personas</span>
            <span class="campo-valor">{{ $reserva->num_personas }}</span>
        </div>

        <div class="mg-reserva-campo">
            <span class="campo-label">Nombre</span>
            <span class="campo-valor">{{ $reserva->nombre }}</span>
        </div>

        <div class="mg-reserva-campo">
            <span class="campo-label">Email</span>
            <span class="campo-valor">{{ $reserva->email }}</span>
        </div>

        @if($reserva->telefono)
            <div class="mg-reserva-campo">
                <span class="campo-label">Teléfono</span>
                <span class="campo-valor">{{ $reserva->telefono }}</span>
            </div>
        @endif

        @if($reserva->notas)
            <div class="mg-reserva-campo">
                <span class="campo-label">Notas</span>
                <span class="campo-valor">{{ $reserva->notas }}</span>
            </div>
        @endif

        @if($reserva->estado !== 'cancelada')
            <div class="mt-4 no-print">
                <div class="mg-info-box mb-3" style="background:rgba(198,148,68,0.08); border:1px solid rgba(198,148,68,0.25); border-radius:8px; padding:.9rem 1rem; font-size:.85rem; color:var(--mg-text-muted);">
                    <i class="bx bx-info-circle me-1" style="color:var(--mg-gold);"></i>
                    Si necesitas cancelar, hazlo con la máxima antelación posible para que otros clientes puedan reservar ese horario.
                </div>
                <div class="text-center">
                    <button id="btn-cancelar"
                            class="btn btn-outline-danger"
                            data-token="{{ $reserva->token }}">
                        <i class="bx bx-x-circle me-1"></i>Cancelar reserva
                    </button>
                </div>
            </div>
        @else
            <div class="mt-4 text-center no-print">
                <p class="text-muted small">Esta reserva ha sido cancelada.</p>
                <a href="{{ route('reservas.public.index') }}" class="btn btn-mg-primary">Hacer nueva reserva</a>
            </div>
        @endif
    </div>

    {{-- Caja informativa sobre cuenta --}}
    @guest
    <div class="no-print" style="max-width:500px; margin:1.5rem auto 0; background:var(--mg-dark-2); border:1px solid var(--mg-border); border-radius:12px; padding:1.25rem 1.5rem;">
        <p style="font-size:.95rem; color:var(--mg-text); margin-bottom:.5rem; font-weight:600;">
            <i class="bx bx-user-circle me-2" style="color:var(--mg-gold);"></i>¿Quieres gestionar tus reservas fácilmente?
        </p>
        <p style="font-size:.85rem; color:var(--mg-text-muted); margin-bottom:1rem;">
            Ahora mismo accedes con el enlace de tu correo. Si creas una cuenta con el email <strong style="color:var(--mg-text);">{{ $reserva->email }}</strong>, podrás ver y cancelar todas tus reservas en cualquier momento sin necesitar ese enlace.
        </p>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('register') }}" class="btn btn-mg-primary btn-sm">
                <i class="bx bx-user-plus me-1"></i>Crear cuenta
            </a>
            <a href="{{ route('login') }}" class="btn btn-sm" style="border:1px solid var(--mg-border); color:var(--mg-text-muted);">
                <i class="bx bx-log-in me-1"></i>Ya tengo cuenta
            </a>
        </div>
    </div>
    @endguest
@endsection

@push('scripts')
<script>
$(function () {
    $('#btn-cancelar').on('click', function () {
        if (!confirm('¿Seguro que quieres cancelar esta reserva? Recuerda cancelar con la máxima antelación posible.')) {
            return;
        }

        const token = $(this).data('token');
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Cancelando...');

        $.ajax({
            url: '/reservas/' + token + '/cancelar',
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                location.reload();
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Error al cancelar.');
                $btn.prop('disabled', false).html('<i class="bx bx-x-circle me-1"></i>Cancelar reserva');
            }
        });
    });
});
</script>
@endpush
