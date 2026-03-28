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

    @include('admin.reservas.partials.modal-detalle')
    @include('admin.reservas.partials.modal-crear')
    @include('admin.reservas.partials.modal-confirmar')
    @include('admin.reservas.partials.modal-cancelar-admin')
@endsection

@push('scripts')
<script>
    // ─── Ver detalle reserva ──────────────────────────────────
    var estadoLabels = {
        confirmada: '<span class="pill-label pill-label-primary">Confirmada</span>',
        cancelada:  '<span class="pill-label pill-label-secondary">Cancelada</span>',
        pendiente:  '<span class="pill-label pill-label-warning">Pendiente</span>',
    };

    $(document).on('click', '.btn-ver-reserva', function () {
        var id = $(this).data('id');
        var $modal = $('#modal-detalle-reserva');

        $('#detalle-reserva-loading').removeClass('d-none');
        $('#detalle-reserva-contenido').addClass('d-none');
        $('#detalle-reserva-error').addClass('d-none').text('');

        new bootstrap.Modal($modal[0]).show();

        $.ajax({
            url: '/admin/reservas/' + id,
            type: 'GET',
            success: function (data) {
                $('#detalle-nombre').text(data.nombre);
                $('#detalle-estado').html(estadoLabels[data.estado] ?? data.estado);
                $('#detalle-email').text(data.email || '—');
                $('#detalle-telefono').text(data.telefono || '—');
                $('#detalle-fecha').text(data.fecha_fmt);
                $('#detalle-horario').text(data.hora_inicio + ' - ' + data.hora_fin);
                $('#detalle-personas').text(data.num_personas);

                if (data.notas) {
                    $('#detalle-notas').text(data.notas);
                    $('#detalle-notas-wrap').removeClass('d-none');
                } else {
                    $('#detalle-notas-wrap').addClass('d-none');
                }

                if (data.notas_admin) {
                    $('#detalle-notas-admin').text(data.notas_admin);
                    $('#detalle-notas-admin-wrap').removeClass('d-none');
                } else {
                    $('#detalle-notas-admin-wrap').addClass('d-none');
                }

                $('#detalle-reserva-loading').addClass('d-none');
                $('#detalle-reserva-contenido').removeClass('d-none');
            },
            error: function () {
                $('#detalle-reserva-loading').addClass('d-none');
                $('#detalle-reserva-error').removeClass('d-none').text('No se pudo cargar la reserva.');
            }
        });
    });

    $('#modal-detalle-reserva').on('hidden.bs.modal', function () {
        $('#detalle-reserva-contenido').addClass('d-none');
        $('#detalle-reserva-error').addClass('d-none').text('');
    });
    
    // ─── Confirmar reserva ────────────────────────────────────
    $(document).on('click', '.btn-confirmar-reserva', function () {
        $('#confirmar-reserva-id').val($(this).data('id'));
        $('#confirmar-reserva-error').addClass('d-none').text('');
        new bootstrap.Modal(document.getElementById('modal-confirmar-reserva')).show();
    });

    $('#btn-confirmar-ok').on('click', function () {
        var id = $('#confirmar-reserva-id').val();
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Confirmando...');

        $.ajax({
            url: '/admin/reservas/' + id + '/confirmar',
            type: 'POST',
            data: { '_token': $('meta[name="csrf-token"]').attr('content'), '_method': 'PATCH' },
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('modal-confirmar-reserva')).hide();
                $('#reservas-table').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                $('#confirmar-reserva-error').removeClass('d-none').text(xhr.responseJSON?.message || 'Error al confirmar la reserva.');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i> Confirmar');
            }
        });
    });

    // ─── Cancelar reserva ─────────────────────────────────────
    $(document).on('click', '.btn-cancelar-reserva', function () {
        $('#cancelar-reserva-id').val($(this).data('id'));
        $('#cancelar-reserva-error').addClass('d-none').text('');
        new bootstrap.Modal(document.getElementById('modal-cancelar-reserva')).show();
    });

    $('#btn-cancelar-ok').on('click', function () {
        var id = $('#cancelar-reserva-id').val();
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Cancelando...');

        $.ajax({
            url: '/admin/reservas/' + id + '/cancelar-admin',
            type: 'POST',
            data: { '_token': $('meta[name="csrf-token"]').attr('content'), '_method': 'PATCH' },
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('modal-cancelar-reserva')).hide();
                $('#reservas-table').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                $('#cancelar-reserva-error').removeClass('d-none').text(xhr.responseJSON?.message || 'Error al cancelar la reserva.');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-x me-1"></i> Sí, cancelar');
            }
        });
    });

    // ─── Nueva reserva desde admin ─────────────────────────────
    $('#modal-crear-reserva').on('hidden.bs.modal', function () {
        $('#form-crear-reserva')[0].reset();
        $('#crear-reserva-error').addClass('d-none').text('');
        $('[data-field-error]').text('');
        $('#tipo-invitado').prop('checked', true).trigger('change');
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
<script>
$(function () {
    // ─── Toggle invitado / usuario ─────────────────────────
    $('input[name="tipo_cliente"]').on('change', function () {
        var tipo = $(this).val();
        if (tipo === 'invitado') {
            $('#seccion-invitado').removeClass('d-none');
            $('#seccion-usuario').addClass('d-none');
            $('#cr-user-id').val('');
            $('#cr-nombre, #cr-email, #cr-telefono').prop('readonly', false).val('');
        } else {
            $('#seccion-invitado').addClass('d-none');
            $('#seccion-usuario').removeClass('d-none');
            $('#cr-select-usuario').val('').trigger('change');
        }
    });

    // ─── Selección de usuario registrado ──────────────────
    $('#cr-select-usuario').on('change', function () {
        var $opt = $(this).find(':selected');
        var id = $(this).val();

        if (!id) {
            $('#cr-user-id').val('');
            $('#cr-usuario-info').addClass('d-none');
            return;
        }

        var nombre = $opt.data('nombre');
        var email  = $opt.data('email');

        $('#cr-user-id').val(id);
        $('#cr-info-nombre').text(nombre);
        $('#cr-info-email').text(email);
        $('#cr-usuario-info').removeClass('d-none');

        // Rellenar los campos ocultos por si el backend los necesita
        $('#cr-nombre').val(nombre);
        $('#cr-email').val(email);
    });
});
</script>
@endpush
