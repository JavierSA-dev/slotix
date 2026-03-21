<div class="modal fade" id="user-modal" tabindex="-1" aria-labelledby="user-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="user-modal-label">@lang('titulos.Crear_Usuario')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="userModalForm" novalidate>
                @csrf
                <input type="hidden" id="userModalId" name="id">
                <div class="modal-body">
                    <div class="row">

                        {{-- Datos principales --}}
                        <div class="col-12 col-md-8">
                            <div class="mb-3">
                                <label for="userModalName" class="form-label">@lang('translation.Nombre') <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="userModalName" name="name"
                                    placeholder="@lang('translation.Nombre')">
                            </div>
                            <div class="mb-3">
                                <label for="userModalEmail" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="userModalEmail" name="email"
                                    placeholder="email@ejemplo.com">
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label for="userModalPassword" class="form-label">
                                            @lang('translation.Contraseña')
                                            <span id="userModalPasswordRequired" class="text-danger">*</span>
                                        </label>
                                        <input type="password" class="form-control" id="userModalPassword"
                                            name="newpassword" autocomplete="new-password">
                                        <div class="form-text text-muted d-none" id="userModalPasswordHint">
                                            @lang('translation.DejarVacioParaMantener')
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label for="userModalPasswordConfirm" class="form-label">
                                            @lang('translation.ConfirmarContraseña')
                                        </label>
                                        <input type="password" class="form-control" id="userModalPasswordConfirm"
                                            name="newpassword_confirmation" autocomplete="new-password">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Avatar --}}
                        <div class="col-12 col-md-4 d-flex flex-column align-items-center">
                            <label class="form-label w-100">Avatar</label>
                            <div class="position-relative d-inline-block mt-2">
                                <div class="position-absolute bottom-0 end-0">
                                    <label for="userModalAvatarInput" class="mb-0" style="cursor:pointer;">
                                        <div class="avatar-xs">
                                            <div class="avatar-title bg-light border rounded-circle text-muted shadow font-size-16">
                                                <i class="bx bxs-image-alt"></i>
                                            </div>
                                        </div>
                                    </label>
                                    <input type="file" name="avatar" id="userModalAvatarInput"
                                        class="d-none" accept="image/png,image/gif,image/jpeg,image/webp">
                                </div>
                                <div class="avatar-lg">
                                    <div class="avatar-title bg-light rounded-circle">
                                        <img id="userModalAvatarPreview"
                                            src="{{ URL::asset('build/images/users/avatar.png') }}"
                                            class="avatar-md h-auto rounded-circle">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Roles --}}
                        <div class="col-12 col-md-8 mt-2">
                            <div class="mb-3">
                                <label for="userModalRoles" class="form-label">@lang('translation.Roles') <span class="text-danger">*</span></label>
                                <select class="form-control" id="userModalRoles" name="role">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Empresas --}}
                        @if(isset($empresas) && $empresas->isNotEmpty())
                        <div class="col-12 mt-2">
                            <div class="mb-3">
                                <label for="userModalEmpresas" class="form-label">Empresas</label>
                                <select class="form-control" id="userModalEmpresas" name="empresas[]" multiple>
                                    @foreach($empresas as $empresa)
                                        <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @endif

                        {{-- Activo --}}
                        <div class="col-12 col-md-4 mt-2 d-flex align-items-center">
                            <div class="form-check form-switch form-switch-lg mb-0" dir="ltr">
                                <input class="form-check-input" type="checkbox" id="userModalActivo"
                                    name="activo" value="1" checked>
                                <label class="form-check-label ms-2 mb-0" for="userModalActivo">
                                    @lang('Activo')
                                </label>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        @lang('botones.Cancelar')
                    </button>
                    <button type="submit" class="btn btn-primary" id="userModalSubmitBtn">
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
    const defaultAvatar = '{{ URL::asset('build/images/users/avatar.png') }}';
    const avatarInput   = document.getElementById('userModalAvatarInput');
    const avatarPreview = document.getElementById('userModalAvatarPreview');

    avatarInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            avatarPreview.src = URL.createObjectURL(this.files[0]);
        }
    });

    new CrudModal({
        modalId: 'user-modal',
        formId: 'userModalForm',
        triggerCreateSelector: '#createUserButton',
        triggerEditSelector: '.btn-edit-user',
        select2Selectors: ['#userModalRoles', '#userModalEmpresas'],
        select2Options: { placeholder: '{{ __('translation.Seleccione') }}...' },
        getUrl: (id) => id ? '/users/' + id : '/users',

        onBeforeCreate: () => {
            document.getElementById('user-modal-label').textContent = '{{ __('titulos.Crear_Usuario') }}';
            document.getElementById('userModalPasswordRequired').classList.remove('d-none');
            document.getElementById('userModalPasswordHint').classList.add('d-none');
        },

        onBeforeEdit: () => {
            document.getElementById('user-modal-label').textContent = '{{ __('titulos.Editar_Usuario') }}';
            document.getElementById('userModalPasswordRequired').classList.add('d-none');
            document.getElementById('userModalPasswordHint').classList.remove('d-none');
        },

        onPopulate: (user) => {
            document.getElementById('userModalId').value          = user.id;
            document.getElementById('userModalName').value        = user.name;
            document.getElementById('userModalEmail').value       = user.email;
            document.getElementById('userModalActivo').checked    = !!user.activo;
            if (user.avatar) { avatarPreview.src = user.avatar; }
            $('#userModalRoles').val(user.role).trigger('change');
            if (document.getElementById('userModalEmpresas')) {
                $('#userModalEmpresas').val(user.empresas || []).trigger('change');
            }
        },

        onReset: () => {
            avatarPreview.src = defaultAvatar;
            avatarInput.value = '';
            if (document.getElementById('userModalEmpresas')) {
                $('#userModalEmpresas').val(null).trigger('change');
            }
        },

        onBeforeSubmit: (formData) => {
            formData.set('activo', document.getElementById('userModalActivo').checked ? 1 : 0);
        },
    });
});
</script>
@endpush
