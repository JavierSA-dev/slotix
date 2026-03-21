<div class="modal fade" id="modal-evento-detalle" tabindex="-1" aria-labelledby="modalEvDetalleLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEvDetalleLabel">
                    <i class="bx bx-calendar-edit me-1"></i>
                    <span id="modal-ev-titulo">Reserva</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="modal-ev-alert" class="alert d-none mb-3"></div>

                <form id="form-reserva-calendario" novalidate>
                    <input type="hidden" id="modal-ev-id">

                    <div class="row g-3">
                        {{-- Estado --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold">Estado</label>
                            <div id="modal-ev-estado-badges" class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-estado-toggle" data-estado="pendiente">
                                    <i class="bx bx-time me-1"></i>Pendiente
                                </button>
                                <button type="button" class="btn btn-sm btn-estado-toggle" data-estado="confirmada">
                                    <i class="bx bx-check me-1"></i>Confirmada
                                </button>
                                <button type="button" class="btn btn-sm btn-estado-toggle" data-estado="cancelada">
                                    <i class="bx bx-x me-1"></i>Cancelada
                                </button>
                            </div>
                            <input type="hidden" id="modal-ev-estado">
                        </div>

                        {{-- Datos personales --}}
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control form-control-sm" id="modal-ev-nombre" name="nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control form-control-sm" id="modal-ev-email" name="email" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control form-control-sm" id="modal-ev-telefono" name="telefono">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Personas</label>
                            <input type="number" class="form-control form-control-sm" id="modal-ev-personas" name="num_personas" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hora inicio</label>
                            <input type="time" class="form-control form-control-sm" id="modal-ev-hora-inicio" name="hora_inicio" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Fecha</label>
                            <input type="date" class="form-control form-control-sm" id="modal-ev-fecha" name="fecha" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones del cliente</label>
                            <textarea class="form-control form-control-sm bg-light" id="modal-ev-notas" name="notas" rows="2" style="resize:none;" readonly></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones del admin</label>
                            <textarea class="form-control form-control-sm" id="modal-ev-notas-admin" name="notas_admin" rows="2" style="resize:none;" placeholder="Notas internas (no visibles para el cliente)"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <a id="modal-ev-gcal" href="#" target="_blank" class="btn btn-sm btn-outline-secondary d-none">
                    <i class="bx bx-calendar-plus me-1"></i>Google Calendar
                </a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-guardar-reserva">
                        <i class="bx bx-save me-1"></i>Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
