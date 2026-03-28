<div class="modal fade" id="modal-detalle-reserva" tabindex="-1" aria-labelledby="modalDetalleReservaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetalleReservaLabel">
                    <i class="bx bx-calendar-check me-1"></i> Detalle de reserva
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="detalle-reserva-loading" class="text-center py-4">
                    <span class="spinner-border spinner-border-sm me-1"></span> Cargando...
                </div>
                <div id="detalle-reserva-error" class="alert alert-danger d-none" role="alert"></div>
                <div id="detalle-reserva-contenido" class="d-none">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-0">Nombre</label>
                            <div id="detalle-nombre" class="fw-semibold"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-0">Estado</label>
                            <div id="detalle-estado"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-0">Email</label>
                            <div id="detalle-email"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-0">Teléfono</label>
                            <div id="detalle-telefono"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small mb-0">Fecha</label>
                            <div id="detalle-fecha" class="fw-semibold"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small mb-0">Horario</label>
                            <div id="detalle-horario"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small mb-0">Personas</label>
                            <div id="detalle-personas"></div>
                        </div>
                        <div class="col-12" id="detalle-notas-wrap">
                            <label class="form-label text-muted small mb-0">Notas del cliente</label>
                            <div id="detalle-notas" class="text-muted fst-italic"></div>
                        </div>
                        <div class="col-12" id="detalle-notas-admin-wrap">
                            <label class="form-label text-muted small mb-0">Notas internas</label>
                            <div id="detalle-notas-admin" class="text-muted fst-italic"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
