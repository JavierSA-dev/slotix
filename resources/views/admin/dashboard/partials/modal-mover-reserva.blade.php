<div class="modal fade" id="modal-confirmar-mover" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-move-horizontal me-1"></i>Mover reserva
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">¿Mover la reserva de <strong id="mover-nombre"></strong>?</p>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="text-center">
                        <div class="text-muted small">Fecha original</div>
                        <div class="fw-semibold" id="mover-fecha-original"></div>
                        <div class="text-muted small" id="mover-hora-original"></div>
                    </div>
                    <div class="text-muted fs-4">→</div>
                    <div class="text-center">
                        <div class="text-muted small">Nueva fecha</div>
                        <div class="fw-semibold text-primary" id="mover-fecha-nueva"></div>
                        <div class="text-muted small" id="mover-hora-nueva"></div>
                    </div>
                </div>
                <div>
                    <label class="form-label">Nota sobre el cambio <span class="text-muted">(opcional)</span></label>
                    <textarea class="form-control form-control-sm" id="mover-notas" rows="2" style="resize:none;" placeholder="Ej: Cambiado a petición del cliente"></textarea>
                </div>
                <div id="mover-alert" class="alert d-none mt-3 mb-0"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" id="btn-mover-cancelar">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm" id="btn-mover-confirmar">
                    <i class="bx bx-check me-1"></i>Confirmar cambio
                </button>
            </div>
        </div>
    </div>
</div>
