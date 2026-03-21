@extends('layouts.master')

@section('title', 'Empresas')

@section('content')
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-warning" id="btn-migrar-todas" title="Ejecutar migraciones pendientes en todas las bases de datos de empresa">
                <i class="bx bx-data me-1"></i> Migrar todas las BDs
            </button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-crear-empresa">
                <i class="bx bx-buildings me-1"></i> Nueva empresa
            </button>
        </div>
    </div>

    <x-crud-datatable :config="$config"></x-crud-datatable>

    @include('admin.empresas.partials.modal-crear')
    @include('admin.empresas.partials.modal-editar')
    @include('admin.empresas.partials.modal-modulos', ['modulos' => $modulos])
@endsection

@push('style')
<style>
.tema-card { cursor: pointer; transition: border-color .2s, box-shadow .2s; }
.tema-card:hover { border-color: #556ee6 !important; }
.tema-card-activo { border-color: #556ee6 !important; box-shadow: 0 0 0 3px rgba(85,110,230,.25); }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    // ─── Tema visual (toggle cards) ───────────────────────────
    $(document).on('change', '.tema-radio-crear, .tema-radio-editar', function () {
        var $modal = $(this).closest('.modal, form');
        $modal.find('.tema-card').removeClass('tema-card-activo');
        $(this).closest('label').find('.tema-card').addClass('tema-card-activo');
    });
    // ─── Crear empresa ────────────────────────────────────────
    $('#modal-crear-empresa').on('hidden.bs.modal', function () {
        $('#form-crear-empresa')[0].reset();
        $('#crear-empresa-error').addClass('d-none').text('');
        $('[data-field-error]', this).text('');
        $('#preview-logo-crear').addClass('d-none').attr('src', '');
    });

    $('#btn-guardar-empresa').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Guardando...');
        $('#crear-empresa-error').addClass('d-none').text('');
        $('[data-field-error="#modal-crear-empresa *"]').text('');

        var formData = new FormData($('#form-crear-empresa')[0]);

        $.ajax({
            url: '{{ route('admin.empresas.store') }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('modal-crear-empresa')).hide();
                $('#empresas-table').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON?.errors || {};
                    Object.keys(errors).forEach(function (campo) {
                        var $el = $('#modal-crear-empresa [data-field-error="' + campo + '"]');
                        if ($el.length) { $el.text(errors[campo][0]); }
                    });
                    if (!Object.keys(errors).length) {
                        $('#crear-empresa-error').removeClass('d-none').text(xhr.responseJSON?.message || 'Error de validación.');
                    }
                } else {
                    $('#crear-empresa-error').removeClass('d-none').text('Ha ocurrido un error. Inténtalo de nuevo.');
                }
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Guardar empresa');
            }
        });
    });

    // ─── Preview logo (crear) ──────────────────────────────────
    $('#crear-logo').on('change', function () {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#preview-logo-crear').removeClass('d-none').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        } else {
            $('#preview-logo-crear').addClass('d-none').attr('src', '');
        }
    });

    // ─── Editar empresa ───────────────────────────────────────
    $(document).on('click', '.btn-editar-empresa', function () {
        var id = $(this).data('id');
        $('#editar-empresa-error').addClass('d-none').text('');
        $('[data-field-error]', '#modal-editar-empresa').text('');
        $('#preview-logo-editar').addClass('d-none').attr('src', '');

        $.get('{{ route('admin.empresas.index') }}/' + id, function (data) {
            $('#editar-empresa-id').val(data.id);
            $('#editar-nombre').val(data.nombre);
            $('#editar-activo').prop('checked', data.activo);
            $('#editar-color-primary').val(data.colores.primary ?? '#c19849');
            $('#editar-color-secondary').val(data.colores.secondary ?? '#535353');
            $('#editar-color-accent').val(data.colores.accent ?? '#00d4e8');

            var temaActual = data.tema || 'neon';
            $('#tema-radios-editar .tema-radio-editar').each(function () {
                var esActivo = $(this).val() === temaActual;
                $(this).prop('checked', esActivo);
                $(this).closest('label').find('.tema-card').toggleClass('tema-card-activo', esActivo);
            });

            if (data.logo) {
                $('#preview-logo-editar').removeClass('d-none').attr('src', data.logo);
            }

            new bootstrap.Modal(document.getElementById('modal-editar-empresa')).show();
        });
    });

    $('#btn-actualizar-empresa').on('click', function () {
        var id = $('#editar-empresa-id').val();
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Guardando...');
        $('#editar-empresa-error').addClass('d-none').text('');
        $('[data-field-error]', '#modal-editar-empresa').text('');

        var formData = new FormData($('#form-editar-empresa')[0]);
        formData.append('_method', 'PUT');

        $.ajax({
            url: '{{ route('admin.empresas.index') }}/' + id,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('modal-editar-empresa')).hide();
                $('#empresas-table').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON?.errors || {};
                    Object.keys(errors).forEach(function (campo) {
                        var $el = $('#modal-editar-empresa [data-field-error="' + campo + '"]');
                        if ($el.length) { $el.text(errors[campo][0]); }
                    });
                    if (!Object.keys(errors).length) {
                        $('#editar-empresa-error').removeClass('d-none').text(xhr.responseJSON?.message || 'Error de validación.');
                    }
                } else {
                    $('#editar-empresa-error').removeClass('d-none').text('Ha ocurrido un error. Inténtalo de nuevo.');
                }
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Guardar cambios');
            }
        });
    });

    // ─── Preview logo (editar) ─────────────────────────────────
    $('#editar-logo').on('change', function () {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#preview-logo-editar').removeClass('d-none').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    // ─── Eliminar empresa ─────────────────────────────────────
    $(document).on('click', '.btn-eliminar-empresa', function () {
        var id = $(this).data('id');
        if (!confirm('¿Seguro que quieres eliminar esta empresa? Esta acción no se puede deshacer.')) { return; }

        $.ajax({
            url: '{{ route('admin.empresas.index') }}/' + id,
            type: 'POST',
            data: { '_method': 'DELETE', '_token': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                $('#empresas-table').DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Error al eliminar la empresa.');
            }
        });
    });

    // ─── Módulos de empresa ───────────────────────────────────
    $(document).on('click', '.btn-modulos-empresa', function () {
        var id = $(this).data('id');
        $('#modulos-empresa-error').addClass('d-none').text('');

        $.get('{{ route('admin.empresas.index') }}/' + id, function (data) {
            $('#modulos-empresa-nombre').text(data.nombre);
            $('#modulos-empresa-id').val(data.id);

            data.modulos.forEach(function (modulo) {
                var $toggle = $('#toggle-modulo-' + modulo.id);
                if ($toggle.length) {
                    $toggle.prop('checked', modulo.activo);
                }
            });

            new bootstrap.Modal(document.getElementById('modal-modulos-empresa')).show();
        });
    });

    $(document).on('change', '.toggle-modulo', function () {
        var empresaId = $('#modulos-empresa-id').val();
        var moduloId = $(this).data('modulo-id');
        var $toggle = $(this);

        $.ajax({
            url: '/admin/empresas/' + empresaId + '/modulos/' + moduloId + '/toggle',
            type: 'POST',
            data: { '_token': $('meta[name="csrf-token"]').attr('content'), 'activo': $toggle.is(':checked') ? 1 : 0 },
            error: function (xhr) {
                $toggle.prop('checked', !$toggle.prop('checked'));
                $('#modulos-empresa-error').removeClass('d-none').text(xhr.responseJSON?.message || 'Error al cambiar el módulo.');
            }
        });
    });

    // ─── Migrar todas las BDs ─────────────────────────────────
    $('#btn-migrar-todas').on('click', function () {
        if (!confirm('¿Ejecutar migraciones pendientes en TODAS las bases de datos de empresa? Esta acción puede tardar varios segundos.')) { return; }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Migrando...');

        $.ajax({
            url: '{{ route('admin.empresas.migrarTodas') }}',
            type: 'POST',
            data: { '_token': $('meta[name="csrf-token"]').attr('content') },
            success: function (data) {
                var resumen = data.resultados.map(function (r) {
                    var icono = r.estado === 'ok' ? '✓' : '✗';
                    var texto = icono + ' ' + r.empresa;
                    if (r.estado === 'error') { texto += ': ' + r.mensaje; }
                    return texto;
                }).join('\n');

                alert('Resultado de la migración:\n\n' + resumen);
            },
            error: function (xhr) {
                alert('Error al ejecutar las migraciones: ' + (xhr.responseJSON?.message || 'Error desconocido'));
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-data me-1"></i> Migrar todas las BDs');
            }
        });
    });
});
</script>
@endpush
