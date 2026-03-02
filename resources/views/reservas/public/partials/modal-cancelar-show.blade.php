<div class="modal fade mg-modal" id="modal-cancelar-show" tabindex="-1" aria-labelledby="modalCancelarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCancelarLabel">
                    <i class="bx bx-x-circle me-1"></i> Cancelar reserva
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center">
                <p style="color:var(--mg-text);">¿Seguro que quieres cancelar esta reserva?</p>
                <p style="font-size:.85rem; color:var(--mg-text-muted);">Esta acción no se puede deshacer. Recuerda cancelar con la máxima antelación posible.</p>
                <div id="modal-cancelar-error" class="text-danger small mt-2 d-none"></div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-mg-secondary btn-sm" data-bs-dismiss="modal">No, mantener</button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="btn-confirmar-cancelar">
                    Sí, cancelar reserva
                </button>
            </div>
        </div>
    </div>
</div>
