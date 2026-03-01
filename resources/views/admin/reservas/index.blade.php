@extends('layouts.master')

@section('title', 'Reservas')

@section('content')
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-crear-reserva">
                <i class="bx bx-calendar-plus me-1"></i> Nueva reserva
            </button>
        </div>
    </div>

    <x-crud-datatable :config="$config"></x-crud-datatable>

    @include('admin.reservas.partials.modal-crear')
@endsection

@push('scripts')
<script>
    $(document).on('click', '.btn-confirmar-reserva', function () {
        var id = $(this).data('id');
        if (!confirm('¿Confirmar esta reserva? Se enviará un email al cliente.')) return;

        $.ajax({
            url: '/admin/reservas/' + id + '/confirmar',
            type: 'POST',
            data: { '_token': $('meta[name="csrf-token"]').attr('content'), '_method': 'PATCH' },
            success: function () {
                $('#reservas-table').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Error al confirmar la reserva.');
            }
        });
    });

    $(document).on('click', '.btn-cancelar-reserva', function () {
        var id = $(this).data('id');
        if (!confirm('¿Cancelar esta reserva?')) return;

        $.ajax({
            url: '/admin/reservas/' + id + '/cancelar-admin',
            type: 'POST',
            data: { '_token': $('meta[name="csrf-token"]').attr('content'), '_method': 'PATCH' },
            success: function () {
                $('#reservas-table').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Error al cancelar la reserva.');
            }
        });
    });

    // ─── Nueva reserva desde admin ─────────────────────────────
    $('#modal-crear-reserva').on('hidden.bs.modal', function () {
        $('#form-crear-reserva')[0].reset();
        $('#crear-reserva-error').addClass('d-none').text('');
        $('[data-field-error]').text('');
    });

    $('#btn-guardar-reserva').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Guardando...');
        $('#crear-reserva-error').addClass('d-none').text('');
        $('[data-field-error]').text('');

        $.ajax({
            url: '/admin/reservas',
            type: 'POST',
            data: $('#form-crear-reserva').serialize(),
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('modal-crear-reserva')).hide();
                $('#reservas-table').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Guardar reserva');
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON?.errors || {};
                    Object.keys(errors).forEach(function (campo) {
                        var $el = $('[data-field-error="' + campo + '"]');
                        if ($el.length) { $el.text(errors[campo][0]); }
                    });
                    var mensaje = xhr.responseJSON?.message || '';
                    if (mensaje && !Object.keys(errors).length) {
                        $('#crear-reserva-error').removeClass('d-none').text(mensaje);
                    }
                } else {
                    $('#crear-reserva-error').removeClass('d-none').text('Ha ocurrido un error. Inténtalo de nuevo.');
                }
            }
        });
    });
</script>
@endpush
