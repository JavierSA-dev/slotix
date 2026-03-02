@extends('layouts.public')

@section('title', 'Mi perfil')

@section('content')
    <div class="mb-4">
        <h1 class="mg-section-title">
            Mi perfil
            <small>Edita tus datos de acceso</small>
        </h1>
    </div>

    <div class="mg-perfil-grid">

        {{-- Datos personales --}}
        <div class="mg-perfil-card">
            <h2 class="mg-perfil-card-titulo"><i class="bx bx-user me-2"></i>Datos personales</h2>

            <div id="perfil-alert" class="d-none mb-3"></div>

            <div class="mb-3">
                <label class="mg-label" for="p-nombre">Nombre</label>
                <input type="text" class="form-control mg-input" id="p-nombre" value="{{ $user->name }}">
                <div class="text-danger small mt-1" id="p-nombre-error"></div>
            </div>
            <div class="mb-3">
                <label class="mg-label" for="p-email">Email</label>
                <input type="email" class="form-control mg-input" id="p-email" value="{{ $user->email }}">
                <div class="text-danger small mt-1" id="p-email-error"></div>
            </div>
            <button class="btn btn-mg-primary btn-sm" id="btn-guardar-perfil">
                Guardar cambios
            </button>
        </div>

        {{-- Cambiar contraseña --}}
        <div class="mg-perfil-card">
            <h2 class="mg-perfil-card-titulo"><i class="bx bx-lock-alt me-2"></i>Cambiar contraseña</h2>

            <div id="password-alert" class="d-none mb-3"></div>

            <div class="mb-3">
                <label class="mg-label" for="p-current-pwd">Contraseña actual</label>
                <input type="password" class="form-control mg-input" id="p-current-pwd" autocomplete="current-password">
                <div class="text-danger small mt-1" id="p-current-pwd-error"></div>
            </div>
            <div class="mb-3">
                <label class="mg-label" for="p-new-pwd">Nueva contraseña</label>
                <input type="password" class="form-control mg-input" id="p-new-pwd" autocomplete="new-password">
                <div class="text-danger small mt-1" id="p-new-pwd-error"></div>
            </div>
            <div class="mb-3">
                <label class="mg-label" for="p-confirm-pwd">Confirmar contraseña</label>
                <input type="password" class="form-control mg-input" id="p-confirm-pwd" autocomplete="new-password">
                <div class="text-danger small mt-1" id="p-confirm-pwd-error"></div>
            </div>
            <button class="btn btn-mg-primary btn-sm" id="btn-cambiar-password">
                Cambiar contraseña
            </button>
        </div>

    </div>
@endsection

@push('scripts')
<script>
$(function () {
    var userId = {{ $user->id }};

    // ─── Guardar datos personales ─────────────────────────
    $('#btn-guardar-perfil').on('click', function () {
        var $btn = $(this);
        $('#p-nombre-error, #p-email-error').text('');
        $('#perfil-alert').addClass('d-none').text('');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Guardando...');

        $.ajax({
            url: '/update-profile/' + userId,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                name: $('#p-nombre').val(),
                email: $('#p-email').val(),
            },
            success: function () {
                $('#perfil-alert')
                    .removeClass('d-none alert-danger')
                    .addClass('mg-alert-success')
                    .text('Datos actualizados correctamente.');
                $btn.prop('disabled', false).text('Guardar cambios');
            },
            error: function (xhr) {
                $btn.prop('disabled', false).text('Guardar cambios');
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON?.errors || {};
                    if (errors.name) { $('#p-nombre-error').text(errors.name[0]); }
                    if (errors.email) { $('#p-email-error').text(errors.email[0]); }
                } else {
                    $('#perfil-alert').removeClass('d-none').addClass('mg-alert-danger').text('Ha ocurrido un error. Inténtalo de nuevo.');
                }
            }
        });
    });

    // ─── Cambiar contraseña ───────────────────────────────
    $('#btn-cambiar-password').on('click', function () {
        var $btn = $(this);
        $('#p-current-pwd-error, #p-new-pwd-error, #p-confirm-pwd-error').text('');
        $('#password-alert').addClass('d-none').text('');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Guardando...');

        $.ajax({
            url: '/update-password/' + userId,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                current_password: $('#p-current-pwd').val(),
                password: $('#p-new-pwd').val(),
                password_confirmation: $('#p-confirm-pwd').val(),
            },
            success: function () {
                $('#password-alert')
                    .removeClass('d-none alert-danger')
                    .addClass('mg-alert-success')
                    .text('Contraseña actualizada correctamente.');
                $('#p-current-pwd, #p-new-pwd, #p-confirm-pwd').val('');
                $btn.prop('disabled', false).text('Cambiar contraseña');
            },
            error: function (xhr) {
                $btn.prop('disabled', false).text('Cambiar contraseña');
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON?.errors || {};
                    if (errors.current_password) { $('#p-current-pwd-error').text(errors.current_password[0]); }
                    if (errors.password) { $('#p-new-pwd-error').text(errors.password[0]); }
                } else if (xhr.status === 401) {
                    $('#p-current-pwd-error').text('La contraseña actual no es correcta.');
                } else {
                    $('#password-alert').removeClass('d-none').addClass('mg-alert-danger').text('Ha ocurrido un error. Inténtalo de nuevo.');
                }
            }
        });
    });
});
</script>
@endpush
