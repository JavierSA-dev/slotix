{{-- Modal Nuevo/Editar Rol --}}
<div class="modal fade" id="role-modal" tabindex="-1" aria-labelledby="role-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-wide modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="role-modal-label">@lang('titulos.Crear_Rol')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="roleModalForm" novalidate>
                @csrf
                <input type="hidden" id="roleModalId" name="id">
                <div class="modal-body">

                    {{-- Nombre --}}
                    <div class="mb-4">
                        <label for="roleModalName" class="form-label">
                            @lang('translation.Nombre') <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="roleModalName" name="name" maxlength="255">
                    </div>

                    <hr>

                    <p class="text-muted mb-3">
                        <i class="fas fa-shield-alt me-1"></i> @lang('translation.User_Permisos')
                    </p>

                    {{-- Tarjetas de permisos agrupados alfabéticamente --}}
                    <div class="row row-cols-2 row-cols-lg-4 g-2">
                        @foreach ($permissions as $group => $groupPermissions)
                            <div class="col">
                                <div class="card border" data-perm-group="{{ $group }}">
                                    <div class="card-header py-2 px-3 bg-light d-flex align-items-center justify-content-between">
                                        <span class="fw-semibold">{{ ucfirst($group) }}</span>
                                        <div class="form-check form-switch mb-0" title="Seleccionar todos">
                                            <input class="form-check-input perm-group-toggle" type="checkbox"
                                                id="group_toggle_{{ $group }}" role="switch">
                                        </div>
                                    </div>
                                    <div class="card-body py-2 px-3">
                                        <div class="row g-0">
                                            @foreach ($groupPermissions->sortBy('name') as $permission)
                                                @php $action = explode('.', $permission->name)[1] ?? $permission->name; @endphp
                                                <div class="col-6">
                                                    <div class="form-check form-switch mb-0">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="perm_{{ $permission->id }}"
                                                            name="permissions[]"
                                                            value="{{ $permission->name }}">
                                                        <label class="form-check-label" for="perm_{{ $permission->id }}">
                                                            {{ $permissionLabels[$action] ?? ucfirst($action) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        @lang('botones.Cancelar')
                    </button>
                    <button type="submit" class="btn btn-primary" id="roleModalSubmitBtn">
                        @lang('botones.Guardar')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@pushOnce('scripts')
    @vite(['resources/js/components/crud-modal.js'])
@endPushOnce

@push('scripts')
<script>
$(function () {
    // Actualiza el estado del switch "seleccionar todos" de un grupo
    function updateGroupToggle(card) {
        const checkboxes = Array.from(card.querySelectorAll('input[name="permissions[]"]'));
        const checkedCount = checkboxes.filter(c => c.checked).length;
        const toggle = card.querySelector('.perm-group-toggle');
        if (!toggle) { return; }
        toggle.checked     = checkedCount > 0 && checkedCount === checkboxes.length;
        toggle.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
    }

    // Actualiza todos los switches de grupo (se llama tras populate/reset)
    function updateAllGroupToggles() {
        document.querySelectorAll('#roleModalForm [data-perm-group]').forEach(updateGroupToggle);
    }

    // Switch de grupo → marcar/desmarcar todos los permisos del grupo
    document.querySelectorAll('.perm-group-toggle').forEach((toggle) => {
        toggle.addEventListener('change', function () {
            const card = this.closest('[data-perm-group]');
            card.querySelectorAll('input[name="permissions[]"]').forEach((cb) => {
                cb.checked = this.checked;
            });
            this.indeterminate = false;
        });
    });

    // Permiso individual → actualizar switch del grupo
    document.querySelectorAll('#roleModalForm input[name="permissions[]"]').forEach((cb) => {
        cb.addEventListener('change', function () {
            const card = this.closest('[data-perm-group]');
            if (card) { updateGroupToggle(card); }
        });
    });

    new CrudModal({
        modalId: 'role-modal',
        formId: 'roleModalForm',
        triggerCreateSelector: '#createRoleButton',
        triggerEditSelector: '.btn-edit-role',
        getUrl: (id) => id ? '/roles/' + id : '/roles',

        onBeforeCreate: () => {
            document.getElementById('role-modal-label').textContent = '{{ __('titulos.Crear_Rol') }}';
        },

        onBeforeEdit: () => {
            document.getElementById('role-modal-label').textContent = '{{ __('titulos.Editar_Rol') }}';
        },

        onPopulate: (role) => {
            document.getElementById('roleModalId').value   = role.id;
            document.getElementById('roleModalName').value = role.name;

            // Desmarcar todos los switches primero
            document.querySelectorAll('#roleModalForm input[name="permissions[]"]')
                .forEach((cb) => { cb.checked = false; });

            // Marcar los permisos del rol
            (role.permissions || []).forEach((permName) => {
                const cb = document.querySelector(
                    '#roleModalForm input[name="permissions[]"][value="' + permName + '"]'
                );
                if (cb) { cb.checked = true; }
            });

            updateAllGroupToggles();
        },

        onReset: () => {
            document.querySelectorAll('.perm-group-toggle').forEach((toggle) => {
                toggle.checked = false;
                toggle.indeterminate = false;
            });
        },
    });
});
</script>
@endpush
