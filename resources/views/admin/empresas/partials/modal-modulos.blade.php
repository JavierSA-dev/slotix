<div class="modal fade" id="modal-modulos-empresa" tabindex="-1" aria-labelledby="modal-modulos-empresa-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-modulos-empresa-titulo">
                    <i class="bx bx-puzzle me-1"></i> Módulos de <span id="modulos-empresa-nombre"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="modulos-empresa-error" class="alert alert-danger d-none" role="alert"></div>

                <input type="hidden" id="modulos-empresa-id">

                <p class="text-muted" style="font-size:.85rem;">Activa o desactiva los módulos disponibles para esta empresa. Los cambios se aplican de inmediato.</p>

                <div class="list-group list-group-flush">
                    @foreach($modulos as $modulo)
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div class="d-flex align-items-center gap-2">
                            @if($modulo->icono)
                                <i class="{{ $modulo->icono }} font-size-18 text-primary"></i>
                            @else
                                <i class="bx bx-extension font-size-18 text-muted"></i>
                            @endif
                            <div>
                                <div class="fw-semibold">{{ $modulo->label }}</div>
                                <div class="text-muted" style="font-size:.75rem;">{{ $modulo->nombre }}</div>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input toggle-modulo"
                                   type="checkbox"
                                   role="switch"
                                   id="toggle-modulo-{{ $modulo->id }}"
                                   data-modulo-id="{{ $modulo->id }}"
                                   style="width:2.2em; height:1.2em; cursor:pointer;">
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
