@extends('layouts.public')

@section('title', 'Mi reserva')

@section('content')
    <div class="mg-reserva-card">
        <div class="mg-reserva-titulo">
            <i class="bx bx-calendar-check me-2"></i>Detalle de tu reserva
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
            <div class="mt-4 text-center">
                <button id="btn-cancelar"
                        class="btn btn-outline-danger"
                        data-token="{{ $reserva->token }}">
                    Cancelar reserva
                </button>
            </div>
        @else
            <div class="mt-4 text-center">
                <p class="text-muted small">Esta reserva ha sido cancelada.</p>
                <a href="{{ route('reservas.public.index') }}" class="btn btn-mg-primary">Hacer nueva reserva</a>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#btn-cancelar').on('click', function () {
        if (!confirm('¿Seguro que quieres cancelar esta reserva?')) {
            return;
        }

        const token = $(this).data('token');
        const $btn = $(this);
        $btn.prop('disabled', true).text('Cancelando...');

        $.ajax({
            url: '/reservas/' + token + '/cancelar',
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                location.reload();
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Error al cancelar.');
                $btn.prop('disabled', false).text('Cancelar reserva');
            }
        });
    });
});
</script>
@endpush
