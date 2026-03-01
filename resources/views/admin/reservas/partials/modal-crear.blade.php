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
                    <div class="row g-3">
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
                            <textarea class="form-control" id="cr-notas" name="notas" rows="2" placeholder="Observaciones (opcional)"></textarea>
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
