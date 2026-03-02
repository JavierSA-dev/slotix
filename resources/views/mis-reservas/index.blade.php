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
        <div class="mg-filtro-group">
            <label for="f-desde">Desde</label>
            <input type="date" class="form-control" id="f-desde" value="{{ $filtros['fecha_desde'] }}">
        </div>
        <div class="mg-filtro-group">
            <label for="f-hasta">Hasta</label>
            <input type="date" class="form-control" id="f-hasta" value="{{ $filtros['fecha_hasta'] }}">
        </div>
        <div class="mg-filtro-group">
            <label for="f-estado">Estado</label>
            <select class="form-select" id="f-estado">
                <option value="" {{ $filtros['estado'] === '' ? 'selected' : '' }}>Todos</option>
                <option value="pendiente" {{ $filtros['estado'] === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="confirmada" {{ $filtros['estado'] === 'confirmada' ? 'selected' : '' }}>Confirmada</option>
                <option value="cancelada" {{ $filtros['estado'] === 'cancelada' ? 'selected' : '' }}>Cancelada</option>
            </select>
        </div>
    </div>

    <div id="mg-reservas-grid">
        @include('mis-reservas.partials.grid')
    </div>

    @include('mis-reservas.partials.modal-cancelar')
@endsection

@push('scripts')
<script>
$(function () {
    // ─── Filtrado AJAX ────────────────────────────────────
    var filtroTimer;

    function filtrar() {
        clearTimeout(filtroTimer);
        filtroTimer = setTimeout(function () {
            $.ajax({
                url: '{{ route('mis-reservas.index') }}',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                data: {
                    fecha_desde: $('#f-desde').val(),
                    fecha_hasta: $('#f-hasta').val(),
                    estado: $('#f-estado').val(),
                },
                success: function (html) {
                    $('#mg-reservas-grid').html(html);
                    bindCancelar();
                }
            });
        }, 400);
    }

    $('#f-desde, #f-hasta, #f-estado').on('change', filtrar);

    // ─── Modal cancelar ───────────────────────────────────
    function bindCancelar() {
        $('#mg-reservas-grid').off('click', '.btn-cancelar-mi-reserva').on('click', '.btn-cancelar-mi-reserva', function () {
            var id = $(this).data('id');
            $('#cancelar-mi-reserva-id').val(id);
            $('#cancelar-mi-reserva-error').addClass('d-none').text('');
            new bootstrap.Modal(document.getElementById('modal-cancelar-mi-reserva')).show();
        });
    }

    bindCancelar();

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
                filtrar();
                $btn.prop('disabled', false).html('Sí, cancelar');
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
