{{-- Modal Nuevo/Editar Permiso --}}
<div class="modal fade" id="permission-modal" tabindex="-1" aria-labelledby="permission-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="permission-modal-label">@lang('titulos.Crear_Permiso')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="permissionModalForm" novalidate>
                @csrf
                <input type="hidden" id="permissionModalId" name="id">
                <div class="modal-body">

                    {{-- Nombre --}}
                    <div class="mb-3">
                        <label for="permissionModalName" class="form-label">
                            @lang('translation.Nombre') <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="permissionModalName" name="name"
                            maxlength="255" placeholder="ej: users.create">
                        <div class="form-text text-muted">
                            Formato recomendado: <code>recurso.accion</code> (ej: <code>users.index</code>)
                        </div>
                    </div>

                    {{-- Roles --}}
                    <div class="mb-3">
                        <label for="permissionModalRoles" class="form-label">@lang('titulos.Roles')</label>
                        <select class="form-control" id="permissionModalRoles" name="roles[]" multiple>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        @lang('botones.Cancelar')
                    </button>
                    <button type="submit" class="btn btn-primary" id="permissionModalSubmitBtn">
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
    new CrudModal({
        modalId: 'permission-modal',
        formId: 'permissionModalForm',
        triggerCreateSelector: '#createPermissionButton',
        triggerEditSelector: '.btn-edit-permission',
        select2Selectors: ['#permissionModalRoles'],
        select2Options: { placeholder: '{{ __('translation.Seleccione') }}...' },
        getUrl: (id) => id ? '/permissions/' + id : '/permissions',

        onBeforeCreate: () => {
            document.getElementById('permission-modal-label').textContent = '{{ __('titulos.Crear_Permiso') }}';
        },

        onBeforeEdit: () => {
            document.getElementById('permission-modal-label').textContent = '{{ __('titulos.Editar_Permiso') }}';
        },

        onPopulate: (permission) => {
            document.getElementById('permissionModalId').value   = permission.id;
            document.getElementById('permissionModalName').value = permission.name;
            $('#permissionModalRoles').val(permission.roles).trigger('change');
        },
    });
});
</script>
@endpush
