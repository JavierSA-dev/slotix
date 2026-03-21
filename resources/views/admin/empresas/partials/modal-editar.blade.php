<div class="modal fade" id="modal-editar-empresa" tabindex="-1" aria-labelledby="modal-editar-empresa-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-editar-empresa-titulo">
                    <i class="bx bx-edit me-1"></i> Editar empresa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="editar-empresa-error" class="alert alert-danger d-none" role="alert"></div>

                <form id="form-editar-empresa" novalidate enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="editar-empresa-id" name="_empresa_id">

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="editar-nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editar-nombre" name="nombre" placeholder="Nombre de la empresa">
                            <div class="text-danger small" data-field-error="nombre"></div>
                        </div>

                        <div class="col-12">
                            <label for="editar-logo" class="form-label">Logo</label>
                            <input type="file" class="form-control" id="editar-logo" name="logo" accept="image/*">
                            <div class="text-muted" style="font-size:.75rem;">Deja vacío para mantener el logo actual.</div>
                            <div class="text-danger small" data-field-error="logo"></div>
                            <img id="preview-logo-editar" src="" alt="Logo actual" class="d-none mt-2 rounded" style="max-height:80px;">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Tema visual</label>
                            <div class="row g-2" id="tema-radios-editar">
                                @foreach(config('temas') as $temaSlug => $temaData)
                                <div class="col-md-4">
                                    <label class="d-block cursor-pointer">
                                        <input type="radio" name="tema" value="{{ $temaSlug }}" class="d-none tema-radio-editar">
                                        <div class="tema-card border rounded p-2 text-center">
                                            <div class="d-flex justify-content-center gap-1 mb-1">
                                                @foreach($temaData['preview'] as $color)
                                                <span style="width:18px;height:18px;border-radius:50%;background:{{ $color }};display:inline-block;"></span>
                                                @endforeach
                                            </div>
                                            <div class="fw-semibold" style="font-size:.85rem;">{{ $temaData['nombre'] }}</div>
                                            <div class="text-muted" style="font-size:.75rem;">{{ $temaData['descripcion'] }}</div>
                                        </div>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            <div class="text-danger small" data-field-error="tema"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Colores de marca</label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label for="editar-color-primary" class="form-label" style="font-size:.8rem;">Color principal</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="color" class="form-control form-control-color" id="editar-color-primary" name="colores[primary]" value="#c19849">
                                        <span class="text-muted" style="font-size:.8rem;">Principal</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="editar-color-secondary" class="form-label" style="font-size:.8rem;">Color secundario</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="color" class="form-control form-control-color" id="editar-color-secondary" name="colores[secondary]" value="#535353">
                                        <span class="text-muted" style="font-size:.8rem;">Secundario</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="editar-color-accent" class="form-label" style="font-size:.8rem;">Color acento</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="color" class="form-control form-control-color" id="editar-color-accent" name="colores[accent]" value="#00d4e8">
                                        <span class="text-muted" style="font-size:.8rem;">Acento</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-danger small" data-field-error="colores"></div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="editar-activo" name="activo" value="1">
                                <label class="form-check-label" for="editar-activo">Empresa activa</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-actualizar-empresa">
                    <i class="bx bx-save me-1"></i> Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>
