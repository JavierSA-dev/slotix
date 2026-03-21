<div class="modal fade" id="modal-demo-acceso" tabindex="-1" aria-labelledby="modal-demo-acceso-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--mg-card-bg); border:1px solid var(--mg-border); border-radius:12px;">
            <div class="modal-header" style="border-color:var(--mg-border);">
                <h5 class="modal-title" id="modal-demo-acceso-label" style="color:var(--mg-gold);">
                    <i class="bx bx-play-circle me-2"></i>Acceso de demostración
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p style="color:var(--mg-text-muted); font-size:.9rem; margin-bottom:1.5rem;">
                    Elige con qué perfil quieres explorar la plataforma:
                </p>
                <div class="d-flex gap-3">
                    <a href="{{ route('demo.acceder', [$empresaSlug ?? '', 'admin']) }}"
                       class="btn btn-mg-primary flex-fill py-3 d-flex flex-column align-items-center gap-1">
                        <i class="bx bx-cog" style="font-size:1.6rem;"></i>
                        <span class="fw-bold">Admin</span>
                        <small style="font-weight:400; opacity:.85;">Gestiona reservas y configuración</small>
                    </a>
                    <a href="{{ route('demo.acceder', [$empresaSlug ?? '', 'usuario']) }}"
                       class="btn flex-fill py-3 d-flex flex-column align-items-center gap-1"
                       style="border:1px solid var(--mg-border); color:var(--mg-text); background:var(--mg-card-bg-2);">
                        <i class="bx bx-user" style="font-size:1.6rem;"></i>
                        <span class="fw-bold">Cliente</span>
                        <small style="font-weight:400; opacity:.7;">Haz reservas como un usuario normal</small>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
