<div class="modal fade" id="modalDiaCerrado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Añadir período cerrado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modalDiaCerradoError" class="alert alert-danger d-none"></div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label for="dc_fecha_inicio" class="form-label fw-semibold">Desde <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="dc_fecha_inicio" min="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="col-6">
                        <label for="dc_fecha_fin" class="form-label fw-semibold">Hasta <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="dc_fecha_fin" min="{{ now()->format('Y-m-d') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="dc_motivo" class="form-label fw-semibold">Motivo <small class="text-muted fw-normal">(opcional)</small></label>
                    <input type="text" class="form-control" id="dc_motivo" placeholder="Ej: Festivo nacional, Vacaciones...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarDiaCerrado">
                    <i class="bx bx-save me-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('dc_fecha_inicio').addEventListener('change', function () {
    document.getElementById('dc_fecha_fin').min = this.value;
    if (document.getElementById('dc_fecha_fin').value < this.value) {
        document.getElementById('dc_fecha_fin').value = this.value;
    }
});

document.getElementById('btnGuardarDiaCerrado').addEventListener('click', function () {
    const fecha_inicio = document.getElementById('dc_fecha_inicio').value;
    const fecha_fin = document.getElementById('dc_fecha_fin').value;
    const motivo = document.getElementById('dc_motivo').value;
    const errorBox = document.getElementById('modalDiaCerradoError');

    errorBox.classList.add('d-none');

    fetch('{{ route('admin.dias-cerrados.store') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ fecha_inicio, fecha_fin, motivo }),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (!ok) {
            const msgs = data.errors ? Object.values(data.errors).flat().join('<br>') : (data.message || 'Error desconocido.');
            errorBox.innerHTML = msgs;
            errorBox.classList.remove('d-none');
            return;
        }

        const tbody = document.getElementById('tablaDiasCerrados');
        const fila = document.createElement('tr');
        fila.id = 'dia-cerrado-' + data.id;
        const rango = data.fecha_inicio_fmt === data.fecha_fin_fmt
            ? data.fecha_inicio_fmt
            : `${data.fecha_inicio_fmt} → ${data.fecha_fin_fmt}`;
        fila.innerHTML = `
            <td>${rango}</td>
            <td>${data.motivo ?? '<span class="text-muted">—</span>'}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="eliminarDiaCerrado(${data.id})">
                    <i class="bx bx-trash"></i>
                </button>
            </td>`;
        tbody.appendChild(fila);

        const vacio = document.getElementById('tablaDiasCerradosVacio');
        if (vacio) { vacio.classList.add('d-none'); }

        document.getElementById('dc_fecha_inicio').value = '';
        document.getElementById('dc_fecha_fin').value = '';
        document.getElementById('dc_motivo').value = '';
        bootstrap.Modal.getInstance(document.getElementById('modalDiaCerrado')).hide();
    });
});

function eliminarDiaCerrado(id) {
    if (!confirm('¿Eliminar este período cerrado?')) { return; }

    fetch(`{{ url('admin/dias-cerrados') }}/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    })
    .then(r => {
        if (r.ok) {
            const fila = document.getElementById('dia-cerrado-' + id);
            if (fila) { fila.remove(); }
        }
    });
}
</script>
@endpush
