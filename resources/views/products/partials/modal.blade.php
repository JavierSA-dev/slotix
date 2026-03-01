{{-- Modal Nuevo Product --}}
<div class="modal fade" id="product-modal" tabindex="-1" aria-labelledby="product-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="product-modal-label">Nuevo Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="productForm">
                <div class="modal-body">
                    {{-- Campo oculto para el ID (edición) --}}
                    <input type="hidden" id="product_id" name="id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="avatar" class="form-label">Avatar</label>
                            <input type="file" class="form-control" id="avatar" name="avatar">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" maxlength="255" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="rol" class="form-label">Rol</label>
                            <input type="email" class="form-control" id="rol" name="rol">
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="activo" name="activo" value="1">
                                <label class="form-check-label" for="activo">Activo <span class="text-danger">*</span></label>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('product-modal');
    const form = document.getElementById('productForm');
    const modalTitle = modal.querySelector('.modal-title');
    const modalInstance = new bootstrap.Modal(modal);
    let isEditMode = false;

    // Limpiar formulario al cerrar el modal
    modal.addEventListener('hidden.bs.modal', function() {
        form.reset();
        form.querySelector('[name="id"]').value = '';
        isEditMode = false;
    });

    // Envío del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const id = form.querySelector('[name="id"]').value;
        isEditMode = id !== '';

        const url = isEditMode
            ? `{{ url('users') }}/${id}`
            : '{{ route("users.store") }}';

        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        // Manejar checkboxes no marcados
        form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            if (!cb.checked) data[cb.name] = 0;
        });

        fetch(url, {
            method: isEditMode ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                modalInstance.hide();
                // Recargar DataTable si existe
                if (typeof dataTable !== 'undefined') {
                    dataTable.ajax.reload(null, false);
                } else if ($.fn.DataTable && $('.dataTable').length) {
                    $('.dataTable').DataTable().ajax.reload(null, false);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Ha ocurrido un error'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error de conexión'
            });
        });
    });

    /**
     * Abre el modal para crear o editar
     * @param {Object|null} data - Datos para edición, null para creación
     */
    window.openProductModal = function(data = null) {
        form.reset();
        form.querySelector('[name="id"]').value = '';

        if (data) {
            // Modo edición
            isEditMode = true;
            modalTitle.textContent = 'Editar Product';

            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    if (input.type === 'checkbox') {
                        input.checked = Boolean(data[key]);
                    } else {
                        input.value = data[key] ?? '';
                    }
                }
            });
        } else {
            // Modo creación
            isEditMode = false;
            modalTitle.textContent = 'Nuevo Product';
        }

        modalInstance.show();
    };

    // Event listener para botones de editar en la DataTable
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-edit')) {
            const btn = e.target.closest('.btn-edit');
            const url = btn.dataset.url;

            fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    openProductModal(response.data);
                } else {
                    Swal.fire('Error', response.message || 'No se pudieron cargar los datos', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Error al cargar los datos', 'error');
            });
        }
    });
});
</script>
@endpush