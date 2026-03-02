<div class="modal fade" id="modal-confirmar-reserva" tabindex="-1" aria-labelledby="modalConfirmarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmarLabel">
                    <i class="bx bx-check-circle me-1"></i> Confirmar reserva
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center">
                <p>¿Confirmar esta reserva?</p>
                <p class="text-muted small">Se enviará un email de confirmación al cliente.</p>
                <div id="confirmar-reserva-error" class="alert alert-danger d-none mt-2" role="alert"></div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success btn-sm" id="btn-confirmar-ok">
                    <i class="bx bx-check me-1"></i> Confirmar
                </button>
            </div>
            <input type="hidden" id="confirmar-reserva-id" value="">
        </div>
    </div>
</div>
