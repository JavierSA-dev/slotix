<div class="modal fade mg-modal" id="modal-reserva" tabindex="-1" aria-labelledby="modalReservaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalReservaLabel">Confirmar reserva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                {{-- Franja seleccionada (informativa) --}}
                <div class="mg-franja-seleccionada">
                    <i class="bx bx-time-five" style="font-size:1.4rem;color:var(--mg-gold);"></i>
                    <div>
                        <div class="mg-franja-hora" id="reserva-franja-display">-</div>
                        <div class="mg-franja-info" id="reserva-fecha-display">-</div>
                    </div>
                </div>

                <form id="form-reserva" novalidate>
                    @csrf
                    <input type="hidden" id="reserva-fecha" name="fecha">
                    <input type="hidden" id="reserva-hora-inicio" name="hora_inicio">

                    @auth
                        <div class="alert alert-info p-2 mb-3" style="background:rgba(198,148,68,0.1);border-color:rgba(198,148,68,0.3);color:var(--mg-text);font-size:0.85rem;">
                            <i class="bx bx-user me-1"></i> Reservando como <strong>{{ auth()->user()->name }}</strong>
                        </div>
                        <input type="hidden" name="nombre" value="{{ auth()->user()->name }}">
                        <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                    @else
                        <div class="mb-3">
                            <label for="res-nombre">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="res-nombre" name="nombre" placeholder="Tu nombre completo" autocomplete="name">
                            <div class="text-danger small mt-1" data-field-error="nombre"></div>
                        </div>
                        <div class="mb-3">
                            <label for="res-email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="res-email" name="email" placeholder="correo@ejemplo.com" autocomplete="email">
                            <div class="text-danger small mt-1" data-field-error="email"></div>
                        </div>
                    @endauth

                    <div class="mb-3">
                        <label for="res-telefono">Teléfono <span class="text-muted">(opcional)</span></label>
                        <input type="tel" class="form-control" id="res-telefono" name="telefono" placeholder="600 000 000" autocomplete="tel">
                    </div>

                    <div class="mb-3">
                        <label for="res-personas">Número de personas <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="res-personas" name="num_personas" min="1" max="20" placeholder="1" value="1">
                        <div class="text-danger small mt-1" data-field-error="num_personas"></div>
                    </div>

                    <div class="mb-3">
                        <label for="res-notas">Notas adicionales</label>
                        <textarea class="form-control" id="res-notas" name="notas" rows="2" placeholder="Cumpleaños, necesidades especiales..."></textarea>
                    </div>

                    <div class="text-danger small mb-2" id="form-reserva-error"></div>
                </form>

                {{-- Resultado exitoso --}}
                <div id="reserva-success" class="mg-success-box d-none">
                    <div class="mg-success-icon">✅</div>
                    <p class="mb-2"><strong>¡Reserva confirmada!</strong></p>
                    <p class="small text-muted mb-3">Te hemos enviado un email de confirmación.</p>
                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <a href="#" id="reserva-gestion-link" class="btn btn-sm btn-outline-light">
                            Ver mi reserva
                        </a>
                        <a href="#" id="btn-gcal-modal" class="btn-gcal d-none" target="_blank" rel="noopener">
                            <i class="bx bx-calendar-plus"></i> Añadir a Google Calendar
                        </a>
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="modal-reserva-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-mg-primary" id="btn-confirmar-reserva">
                    Confirmar reserva
                </button>
            </div>
        </div>
    </div>
</div>
