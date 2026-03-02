@extends('layouts.public')

@section('title', 'Mis reservas')

@section('content')
    <div class="mb-4">
        <h1 class="mg-section-title">
            Mis reservas
            <small>Gestiona tus reservas</small>
        </h1>
    </div>

    {{-- Filtros --}}
    <div class="mg-filtros">
        <form method="GET" action="{{ route('mis-reservas.index') }}">
            <div class="mg-filtros-row">
                <div class="mg-filtro-group">
                    <label for="f-desde">Desde</label>
                    <input type="date" class="form-control" id="f-desde" name="fecha_desde" value="{{ $filtros['fecha_desde'] }}">
                </div>
                <div class="mg-filtro-group">
                    <label for="f-hasta">Hasta</label>
                    <input type="date" class="form-control" id="f-hasta" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] }}">
                </div>
                <div class="mg-filtro-group">
                    <label for="f-estado">Estado</label>
                    <select class="form-select" id="f-estado" name="estado">
                        <option value="" {{ $filtros['estado'] === '' ? 'selected' : '' }}>Todos</option>
                        <option value="pendiente" {{ $filtros['estado'] === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="confirmada" {{ $filtros['estado'] === 'confirmada' ? 'selected' : '' }}>Confirmada</option>
                        <option value="cancelada" {{ $filtros['estado'] === 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                    </select>
                </div>
                <div class="mg-filtro-group" style="min-width:auto; flex:0;">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-mg-primary btn-sm">Filtrar</button>
                </div>
                <div class="mg-filtro-group" style="min-width:auto; flex:0;">
                    <label>&nbsp;</label>
                    <a href="{{ route('reservas.public.index') }}" class="btn btn-mg-secondary btn-sm">+ Nueva reserva</a>
                </div>
            </div>
        </form>
    </div>

    @if($reservas->isEmpty())
        <div class="mg-empty-state">
            <div class="mg-empty-icon">📅</div>
            <p>No hay reservas para el rango seleccionado.</p>
            <a href="{{ route('reservas.public.index') }}" class="btn btn-mg-primary mt-2">Hacer una reserva</a>
        </div>
    @else
        <div class="mg-mis-reservas-grid">
            @foreach($reservas as $reserva)
                <div class="mg-reserva-item">
                    <div class="mg-ri-header">
                        <div>
                            <div class="mg-ri-fecha">{{ $reserva->fecha->format('d/m/Y') }}</div>
                            <div class="mg-ri-hora">{{ $reserva->hora_inicio_fmt }} – {{ $reserva->hora_fin_fmt }}</div>
                        </div>
                        <span class="mg-estado-pill {{ $reserva->estado }}">{{ ucfirst($reserva->estado) }}</span>
                    </div>
                    <div class="mg-ri-body">
                        <span><i class="bx bx-group me-1"></i>{{ $reserva->num_personas }} persona{{ $reserva->num_personas > 1 ? 's' : '' }}</span>
                        @if($reserva->notas)
                            <span class="mg-ri-notas">{{ Str::limit($reserva->notas, 60) }}</span>
                        @endif
                    </div>
                    <div class="mg-ri-actions">
                        <a href="{{ $reserva->google_calendar_url }}" target="_blank" rel="noopener" class="btn-gcal">
                            <i class="bx bx-calendar-plus"></i> Google Calendar
                        </a>
                        @if($reserva->estado !== 'cancelada')
                            <button class="btn btn-outline-danger btn-sm btn-cancelar-mi-reserva"
                                    data-id="{{ $reserva->id }}"
                                    style="margin-left:auto;">
                                Cancelar
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @include('mis-reservas.partials.modal-cancelar')
@endsection

@push('scripts')
<script>
$(function () {
    $('.btn-cancelar-mi-reserva').on('click', function () {
        var id = $(this).data('id');
        $('#cancelar-mi-reserva-id').val(id);
        $('#cancelar-mi-reserva-error').addClass('d-none').text('');
        new bootstrap.Modal(document.getElementById('modal-cancelar-mi-reserva')).show();
    });

    $('#btn-confirmar-cancelar-mi').on('click', function () {
        var id = $('#cancelar-mi-reserva-id').val();
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Cancelando...');

        $.ajax({
            url: '/mis-reservas/' + id + '/cancelar',
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('modal-cancelar-mi-reserva')).hide();
                location.reload();
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('Sí, cancelar');
                $('#cancelar-mi-reserva-error').text(xhr.responseJSON?.message || 'Error al cancelar.').removeClass('d-none');
            }
        });
    });
});
</script>
@endpush
