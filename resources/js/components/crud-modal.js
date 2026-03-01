/**
 * CrudModal — Clase para gestionar modales Bootstrap CRUD.
 *
 * Garantiza que el modal nunca se muestra hasta que los datos estén
 * completamente cargados (para edición: fetch → populate → show).
 *
 * Compatible con Select2, Bootstrap 5 y SweetAlert2.
 */
class CrudModal {
    /**
     * @param {object}   config
     * @param {string}   config.modalId                  - ID del elemento modal Bootstrap
     * @param {string}   config.formId                   - ID del formulario dentro del modal
     * @param {function} config.getUrl                   - function(id) → URL del endpoint CRUD
     * @param {string}   [config.triggerCreateSelector]  - Selector CSS del botón "Nuevo"
     * @param {string}   [config.triggerEditSelector]    - Selector CSS del botón "Editar" (requiere data-url)
     * @param {string[]} [config.select2Selectors]       - Selectores de campos a inicializar con Select2
     * @param {object}   [config.select2Options]         - Opciones adicionales para Select2
     * @param {string}   [config.editBtnOriginalHtml]    - HTML del botón editar en estado normal
     * @param {string}   [config.editBtnLoadingHtml]     - HTML del botón editar mientras carga
     * @param {function} [config.onBeforeCreate]         - Se llama antes de abrir en modo creación
     * @param {function} [config.onBeforeEdit]           - Se llama antes de cargar datos (antes del fetch)
     * @param {function} [config.onPopulate]             - function(data, crudModal) → puebla los campos
     * @param {function} [config.onReset]                - function(crudModal) → reset adicional de campos
     * @param {function} [config.onBeforeSubmit]         - function(formData, id) → modifica el FormData
     * @param {function} [config.onSuccess]              - function(data, crudModal) → callback tras guardar
     */
    constructor(config) {
        this.config = Object.assign({
            select2Selectors: [],
            select2Options: {},
            editBtnOriginalHtml: '<i class="fa fa-edit"></i>',
            editBtnLoadingHtml: '<i class="fa fa-spinner fa-spin"></i>',
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.content,
        }, config);

        this.modal  = document.getElementById(this.config.modalId);
        this.$modal = $('#' + this.config.modalId);
        this.form   = document.getElementById(this.config.formId);

        if (!this.modal || !this.form) {
            console.error('CrudModal: elemento modal o formulario no encontrado.', this.config);
            return;
        }

        this._bindEvents();
    }

    // ── API pública ───────────────────────────────────────────────────────────

    /**
     * Abre el modal.
     * - Sin editUrl → modo creación (abre el modal directamente).
     * - Con editUrl → modo edición (fetch → populate → show).
     *
     * @param {string|null} editUrl
     */
    open(editUrl = null) {
        this._reset();
        this._initSelect2();

        if (editUrl) {
            this._openForEdit(editUrl);
        } else {
            this._openForCreate();
        }
    }

    // ── Privado ───────────────────────────────────────────────────────────────

    _bindEvents() {
        this.$modal.on('hidden.bs.modal', () => this._reset());

        if (this.config.triggerCreateSelector) {
            $(document).on('click', this.config.triggerCreateSelector, (e) => {
                e.preventDefault();
                this.open(null);
            });
        }

        if (this.config.triggerEditSelector) {
            $(document).on('click', this.config.triggerEditSelector, (e) => {
                this.open($(e.currentTarget).data('url'));
            });
        }

        this.form.addEventListener('submit', (e) => this._handleSubmit(e));
    }

    _openForCreate() {
        if (this.config.onBeforeCreate) {
            this.config.onBeforeCreate(this);
        }
        bootstrap.Modal.getOrCreateInstance(this.modal).show();
    }

    _openForEdit(editUrl) {
        if (this.config.onBeforeEdit) {
            this.config.onBeforeEdit(this);
        }

        const triggerBtn = document.querySelector(
            this.config.triggerEditSelector + '[data-url="' + editUrl + '"]'
        );
        this._setTriggerLoading(triggerBtn, true);

        fetch(editUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.config.csrfToken,
            },
        })
        .then((r) => r.json())
        .then((data) => {
            if (this.config.onPopulate) {
                this.config.onPopulate(data, this);
            }
            this._setTriggerLoading(triggerBtn, false);
            bootstrap.Modal.getOrCreateInstance(this.modal).show();
        })
        .catch(() => {
            this._setTriggerLoading(triggerBtn, false);
            Swal.fire('Error', 'No se pudieron cargar los datos.', 'error');
        });
    }

    _handleSubmit(e) {
        e.preventDefault();
        this._clearErrors();

        const idField = this.form.querySelector('[name="id"]');
        const id      = idField ? idField.value : null;
        const url     = this.config.getUrl(id);

        const formData = new FormData(this.form);
        if (this.config.onBeforeSubmit) {
            this.config.onBeforeSubmit(formData, id);
        }
        if (id) {
            formData.append('_method', 'PUT');
        }

        const submitBtn = this.form.querySelector('[type="submit"]');
        if (submitBtn) { submitBtn.disabled = true; }

        fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.config.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        })
        .then((r) => {
            if (submitBtn) { submitBtn.disabled = false; }

            if (r.status === 422) {
                return r.json().then((data) => this._showErrors(data.errors ?? {}));
            }
            if (!r.ok) {
                return r.json().catch(() => ({})).then((data) => {
                    Swal.fire('Error', data.message ?? 'Error al guardar.', 'error');
                });
            }

            return r.json().then((data) => {
                bootstrap.Modal.getInstance(this.modal).hide();
                if (this.config.onSuccess) {
                    this.config.onSuccess(data, this);
                } else {
                    Swal.fire({ icon: 'success', title: data.message, timer: 2000, showConfirmButton: false });
                    $(document).trigger('crud:reload');
                }
            });
        })
        .catch(() => {
            if (submitBtn) { submitBtn.disabled = false; }
            Swal.fire('Error', 'Error de conexión.', 'error');
        });
    }

    _initSelect2() {
        this.config.select2Selectors.forEach((selector) => {
            if (!$(selector).hasClass('select2-hidden-accessible')) {
                $(selector).select2(Object.assign({
                    dropdownParent: this.$modal,
                    allowClear: true,
                    width: '100%',
                }, this.config.select2Options));
            }
        });
    }

    _reset() {
        this.form.reset();

        const idField = this.form.querySelector('[name="id"]');
        if (idField) { idField.value = ''; }

        this.config.select2Selectors.forEach((selector) => {
            if ($(selector).hasClass('select2-hidden-accessible')) {
                $(selector).val(null).trigger('change');
            }
        });

        this._clearErrors();

        if (this.config.onReset) {
            this.config.onReset(this);
        }
    }

    _setTriggerLoading(btn, loading) {
        if (!btn) { return; }
        btn.disabled  = loading;
        btn.innerHTML = loading
            ? this.config.editBtnLoadingHtml
            : this.config.editBtnOriginalHtml;
    }

    _showErrors(errors) {
        Object.keys(errors).forEach((key) => {
            const fieldName = key.replace(/\.\d+$/, '');
            const input = this.form.querySelector('[name="' + fieldName + '"]')
                       || this.form.querySelector('[name="' + fieldName + '[]"]');
            if (input) {
                input.classList.add('is-invalid');
                const div = document.createElement('div');
                div.className = 'invalid-feedback d-block';
                div.textContent = errors[key][0];
                input.closest('.mb-3')?.appendChild(div);
            }
        });
    }

    _clearErrors() {
        this.form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        this.form.querySelectorAll('.invalid-feedback').forEach((el) => el.remove());
    }
}

window.CrudModal = CrudModal;
