<div class="modal fade" id="modal-cancelar-reserva" tabindex="-1" aria-labelledby="modalCancelarAdminLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCancelarAdminLabel">
                    <i class="bx bx-x-circle me-1"></i> Cancelar reserva
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center">
                <p>¿Cancelar esta reserva?</p>
                <p class="text-muted small">Esta acción no se puede deshacer.</p>
                <div id="cancelar-reserva-error" class="alert alert-danger d-none mt-2" role="alert"></div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">No, mantener</button>
                <button type="button" class="btn btn-danger btn-sm" id="btn-cancelar-ok">
                    <i class="bx bx-x me-1"></i> Sí, cancelar
                </button>
            </div>
            <input type="hidden" id="cancelar-reserva-id" value="">
        </div>
    </div>
</div>
