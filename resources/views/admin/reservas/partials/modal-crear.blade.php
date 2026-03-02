<div class="modal fade" id="modal-crear-reserva" tabindex="-1" aria-labelledby="modal-crear-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-crear-titulo">
                    <i class="bx bx-calendar-plus me-1"></i> Nueva reserva
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="crear-reserva-error" class="alert alert-danger d-none" role="alert"></div>

                <form id="form-crear-reserva" novalidate>
                    @csrf
                    <input type="hidden" id="cr-user-id" name="user_id" value="">

                    {{-- Toggle tipo de cliente --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de cliente</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_cliente" id="tipo-invitado" value="invitado" checked>
                                <label class="form-check-label" for="tipo-invitado">Invitado (sin cuenta)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_cliente" id="tipo-usuario" value="usuario">
                                <label class="form-check-label" for="tipo-usuario">Usuario registrado</label>
                            </div>
                        </div>
                    </div>

                    {{-- Sección invitado --}}
                    <div id="seccion-invitado">
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label for="cr-nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cr-nombre" name="nombre" placeholder="Nombre del cliente">
                                <div class="text-danger small" data-field-error="nombre"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="cr-email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="cr-email" name="email" placeholder="correo@ejemplo.com">
                                <div class="text-danger small" data-field-error="email"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="cr-telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="cr-telefono" name="telefono" placeholder="Opcional">
                                <div class="text-danger small" data-field-error="telefono"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Sección usuario registrado --}}
                    <div id="seccion-usuario" class="d-none mb-3">
                        <label for="cr-select-usuario" class="form-label">Usuario <span class="text-danger">*</span></label>
                        <select class="form-select" id="cr-select-usuario">
                            <option value="">— Selecciona un usuario —</option>
                            @foreach($usuarios as $u)
                                <option value="{{ $u->id }}"
                                        data-nombre="{{ $u->name }}"
                                        data-email="{{ $u->email }}">
                                    {{ $u->name }} — {{ $u->email }}
                                </option>
                            @endforeach
                        </select>
                        <div class="text-danger small" data-field-error="user_id"></div>

                        <div id="cr-usuario-info" class="d-none mt-2 p-2 bg-light rounded border" style="font-size:.85rem;">
                            <div><strong>Nombre:</strong> <span id="cr-info-nombre"></span></div>
                            <div><strong>Email:</strong> <span id="cr-info-email"></span></div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cr-fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="cr-fecha" name="fecha" min="{{ now()->format('Y-m-d') }}">
                            <div class="text-danger small" data-field-error="fecha"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="cr-hora" class="form-label">Hora inicio <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="cr-hora" name="hora_inicio">
                            <div class="text-danger small" data-field-error="hora_inicio"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="cr-personas" class="form-label">Nº personas <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="cr-personas" name="num_personas" min="1" max="50" value="1">
                            <div class="text-danger small" data-field-error="num_personas"></div>
                        </div>

                        <div class="col-12">
                            <label for="cr-notas" class="form-label">Notas</label>
                            <textarea class="form-control" id="cr-notas" name="notas" rows="2" placeholder="Observaciones (opcional)" style="resize:none;"></textarea>
                            <div class="text-danger small" data-field-error="notas"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-reserva">
                    <i class="bx bx-save me-1"></i> Guardar reserva
                </button>
            </div>
        </div>
    </div>
</div>

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
