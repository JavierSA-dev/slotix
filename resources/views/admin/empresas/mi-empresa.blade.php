@extends('layouts.master')

@section('title', 'Mi empresa')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div id="mi-empresa-alert-success" class="alert alert-success d-none" role="alert"></div>
            <div id="mi-empresa-alert-error" class="alert alert-danger d-none" role="alert"></div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bx bx-buildings me-1"></i> Datos de la empresa</h5>
                </div>
                <div class="card-body">
                    <form id="form-mi-empresa" novalidate enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="{{ $empresa->nombre }}">
                                <div class="text-danger small" data-field-error="nombre"></div>
                            </div>

                            <div class="col-12">
                                <label for="logo" class="form-label">Logo</label>
                                <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                <div class="text-muted" style="font-size:.75rem;">Deja vacío para mantener el logo actual.</div>
                                <div class="text-danger small" data-field-error="logo"></div>
                                @if($empresa->logo)
                                    <img id="preview-logo" src="{{ Storage::url($empresa->logo) }}" alt="Logo actual" class="mt-2 rounded" style="max-height:80px;">
                                @else
                                    <img id="preview-logo" src="" alt="Logo actual" class="d-none mt-2 rounded" style="max-height:80px;">
                                @endif
                            </div>

                            <div class="col-12">
                                <label class="form-label">Tema visual</label>
                                <div class="row g-2">
                                    @foreach(config('temas') as $slug => $tema)
                                    <div class="col-md-4">
                                        <label class="d-block cursor-pointer">
                                            <input type="radio" name="tema" value="{{ $slug }}" class="d-none tema-radio"
                                                {{ ($empresa->tema ?? 'neon') === $slug ? 'checked' : '' }}>
                                            <div class="tema-card border rounded p-2 text-center {{ ($empresa->tema ?? 'neon') === $slug ? 'tema-card-activo' : '' }}">
                                                <div class="d-flex justify-content-center gap-1 mb-1">
                                                    @foreach($tema['preview'] as $color)
                                                    <span style="width:18px;height:18px;border-radius:50%;background:{{ $color }};display:inline-block;"></span>
                                                    @endforeach
                                                </div>
                                                <div class="fw-semibold" style="font-size:.85rem;">{{ $tema['nombre'] }}</div>
                                                <div class="text-muted" style="font-size:.75rem;">{{ $tema['descripcion'] }}</div>
                                            </div>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                                <div class="text-danger small" data-field-error="tema"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Colores de marca</label>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label for="color-primary" class="form-label" style="font-size:.8rem;">Color principal</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="color" class="form-control form-control-color" id="color-primary" name="colores[primary]" value="{{ $empresa->getColoresDefecto()['primary'] }}">
                                            <span class="text-muted" style="font-size:.8rem;">Principal</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="color-secondary" class="form-label" style="font-size:.8rem;">Color secundario</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="color" class="form-control form-control-color" id="color-secondary" name="colores[secondary]" value="{{ $empresa->getColoresDefecto()['secondary'] }}">
                                            <span class="text-muted" style="font-size:.8rem;">Secundario</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="color-accent" class="form-label" style="font-size:.8rem;">Color acento</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="color" class="form-control form-control-color" id="color-accent" name="colores[accent]" value="{{ $empresa->getColoresDefecto()['accent'] }}">
                                            <span class="text-muted" style="font-size:.8rem;">Acento</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-end">
                    <button type="button" class="btn btn-primary" id="btn-guardar-mi-empresa">
                        <i class="bx bx-save me-1"></i> Guardar cambios
                    </button>
                </div>
            </div>

        </div>
    </div>
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
    $(document).on('change', '.tema-radio', function () {
        $('.tema-card').removeClass('tema-card-activo');
        $(this).closest('label').find('.tema-card').addClass('tema-card-activo');
    });

    $('#logo').on('change', function () {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#preview-logo').removeClass('d-none').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    $('#btn-guardar-mi-empresa').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Guardando...');
        $('#mi-empresa-alert-success').addClass('d-none').text('');
        $('#mi-empresa-alert-error').addClass('d-none').text('');
        $('[data-field-error]').text('');

        var formData = new FormData($('#form-mi-empresa')[0]);

        $.ajax({
            url: '{{ route('admin.mi-empresa.update') }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (data) {
                $('#mi-empresa-alert-success').removeClass('d-none').text(data.message || 'Cambios guardados correctamente.');
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON?.errors || {};
                    Object.keys(errors).forEach(function (campo) {
                        var $el = $('[data-field-error="' + campo + '"]');
                        if ($el.length) { $el.text(errors[campo][0]); }
                    });
                    if (!Object.keys(errors).length) {
                        $('#mi-empresa-alert-error').removeClass('d-none').text(xhr.responseJSON?.message || 'Error de validación.');
                    }
                } else {
                    $('#mi-empresa-alert-error').removeClass('d-none').text('Ha ocurrido un error. Inténtalo de nuevo.');
                }
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Guardar cambios');
            }
        });
    });
});
</script>
@endpush
