/**
 * Clase para manejar CRUDs con Yajra DataTables
 */
class CrudTable {
    constructor(config) {
        this.config = config;
        this.table = null;
        this.init();
    }

    init() {
        if (!this.config.table || !this.config.table.columns) {
            console.error('CrudTable: Configuración inválida o incompleta (table.columns faltante)');
            return;
        }

        this.initializeFilters();
        this.restoreFiltersFromStorage();
        this.initializeTable();
        this.initializeEvents();
    }

    initializeTable() {
        const self = this;

        // Mapeo directo de columnas.
        // El PHP ya normalizó 'columns' a objetos con { header, data, name }
        const columnsConfig = this.config.table.columns.map(col => {
            // Pasamos 'data' y 'name' básicos.
            return Object.assign({
                data: col.data,
                name: col.name
            }, col);
        });

        this.table = $(`#${this.config.table.id}`).DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            pageLength: this.config.table.pageLength || 100,
            ajax: {
                url: this.config.urls.ajax,
                data: function(d) {
                    self.processFilterData(d);
                }
            },
            dom: this.config.table.dom || '<"top">rt<"bottom d-flex justify-content-between align-items-center"l i p><"clear">',
            language: { url: this.config.urls.idioma },
            columns: columnsConfig,
            scrollX: true,
            scrollY: this.calculateScrollY(),
            scrollCollapse: true,
            autoWidth: false
        });

        // Recalcular tras el primer draw (cuando el header ya existe en el DOM)
        this.table.one('draw', () => {
            const adjustedHeight = this.calculateScrollY();
            const scrollBody = $(`#${this.config.table.id}`).closest('.dataTables_scrollBody');
            scrollBody.css('max-height', adjustedHeight);
        });

        // Recalcular altura al redimensionar la ventana
        $(window).on('resize', this.debounce(() => {
            const newHeight = this.calculateScrollY();
            const scrollBody = $(`#${this.config.table.id}`).closest('.dataTables_scrollBody');
            scrollBody.css('max-height', newHeight);
        }, 200));

    }

    calculateScrollY() {
        const wrapper = document.querySelector('.datatable-wrapper');
        if (!wrapper) return '60vh';

        const wrapperTop = wrapper.getBoundingClientRect().top;

        const filtersHeight = wrapper.querySelector('.d-flex.flex-wrap')
            ? wrapper.querySelector('.d-flex.flex-wrap').offsetHeight + 16
            : 0;

        // Altura del header de DataTables (ya renderizado tras el primer draw)
        const scrollHead = wrapper.querySelector('.dataTables_scrollHead');
        const headerHeight = scrollHead ? scrollHead.offsetHeight : 0;

        const reservedBottom = 155; // paginación (~50) + footer (~55) + padding/margin (~50)
        const available = window.innerHeight - wrapperTop - filtersHeight - headerHeight - reservedBottom;

        return Math.max(available, 200) + 'px';
    }

    getStorageKey() {
        return `crud_table_filters_${this.config.table.id}`;
    }

    saveFiltersToStorage() {
        const filtersState = {};
        this.config.filters.forEach(filter => {
            const $el = $(filter.selector);
            if ($el.length === 0) return;

            let val;
            if (filter.type === 'checkbox' || filter.type === 'switch') val = $el.prop('checked') ? 1 : 0;
            else val = $el.val();

            if (val !== '' && val !== null && val !== undefined) {
                filtersState[filter.id] = val;
            }
        });
        localStorage.setItem(this.getStorageKey(), JSON.stringify(filtersState));
    }

    restoreFiltersFromStorage() {
        const savedState = localStorage.getItem(this.getStorageKey());
        if (!savedState) return;

        try {
            const filtersState = JSON.parse(savedState);
            Object.keys(filtersState).forEach(filterId => {
                const filterConfig = this.config.filters.find(f => f.id === filterId);
                if (filterConfig) {
                    const $el = $(filterConfig.selector);
                    const val = filtersState[filterId];

                    if (filterConfig.type === 'checkbox' || filterConfig.type === 'switch') {
                        $el.prop('checked', val == 1);
                    } else {
                        $el.val(val);
                        $el.trigger('change.select2');
                    }
                }
            });
        } catch (e) {
            console.error('Error restaurando filtros:', e);
            localStorage.removeItem(this.getStorageKey());
        }
    }

    clearAllFilters() {
        this.config.filters.forEach(filter => {
            const $el = $(filter.selector);

            if (filter.type === 'checkbox') {
                $el.prop('checked', false);
            } else if (filter.type === 'switch') {
                // El switch vuelve a su estado por defecto, no necesariamente apagado
                $el.prop('checked', filter.defaultOn === true);
            } else if (filter.type === 'select2') {
                $el.val(null).trigger('change.select2');
            } else if (filter.type === 'select') {
                $el.prop('selectedIndex', 0).trigger('change');
            } else {
                $el.val('');
                $el.trigger('change');
            }
        });

        localStorage.removeItem(this.getStorageKey());
        this.table.ajax.reload();
    }

    processFilterData(d) {
        this.config.filters.forEach(filter => {
            const $el = $(filter.selector);
            if ($el.length === 0) return;

            if (filter.type === 'checkbox' || filter.type === 'switch') d[filter.id] = $el.prop('checked') ? 1 : 0;
            else d[filter.id] = $el.val();
        });
    }

    debounce(func, wait) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    initializeFilters() {
        const self = this;
        this.config.filters.forEach(filter => {
            const $el = $(filter.selector);

            // Carga dinámica de opciones para selects
            if (filter.url && (filter.type === 'select' || filter.type === 'select2')) {
                $.get(filter.url, function(data) {
                    $el.empty();
                    // Agregar placeholder si existe
                    if (filter.placeholder) {
                        $el.append(new Option(filter.placeholder, ''));
                    }

                    // Manejar Array de objetos o Objeto Clave-Valor
                    if (Array.isArray(data)) {
                        data.forEach(item => {
                            const val = item.id !== undefined ? item.id : item;
                            const text = item.text || item.name || item.label || item;
                            $el.append(new Option(text, val));
                        });
                    } else if (typeof data === 'object') {
                        $.each(data, function(key, value) {
                            $el.append(new Option(value, key));
                        });
                    }

                    // Re-aplicar filtro guardado tras la carga asíncrona
                    const savedState = localStorage.getItem(self.getStorageKey());
                    if (savedState) {
                        try {
                            const filtersState = JSON.parse(savedState);
                            if (filtersState[filter.id] !== undefined) {
                                $el.val(filtersState[filter.id]);
                                if (filter.type === 'select2') $el.trigger('change.select2');
                            }
                        } catch (e) {}
                    }
                });
            }

            if (filter.type === 'daterange') {
                if (typeof $el.daterangepicker === 'function') {
                    const dateFormat = filter.format || 'DD/MM/YYYY';
                    $el.daterangepicker({
                        autoUpdateInput: false,
                        locale: {
                            format: dateFormat,
                            applyLabel: 'Aplicar',
                            cancelLabel: 'Limpiar',
                            daysOfWeek: ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'],
                            monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                                         'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                            firstDay: 0
                        }
                    });
                    $el.on('apply.daterangepicker', function(ev, picker) {
                        $(this).val(picker.startDate.format(dateFormat) + ' - ' + picker.endDate.format(dateFormat));
                        self.saveFiltersToStorage();
                        self.table.draw();
                    });
                    $el.on('cancel.daterangepicker', function() {
                        $(this).val('');
                        self.saveFiltersToStorage();
                        self.table.draw();
                    });
                }
            }
            else if (filter.type === 'select2') {
                if (typeof $el.select2 === 'function') {
                    $el.select2({
                        placeholder: $el.data('placeholder') || filter.placeholder || 'Seleccionar',
                        allowClear: true,
                        width: filter.style ? 'resolve' : '100%'
                    });

                    $el.on('change.select2', function() {
                        self.saveFiltersToStorage();
                        self.table.draw();
                    });
                }
            }
            else {
                const eventName = filter.event || 'change input';

                if ((filter.type === 'input' || filter.type === 'text') && (eventName.includes('keyup') || eventName.includes('input'))) {
                    $el.on(eventName, self.debounce(() => {
                        self.saveFiltersToStorage();
                        self.table.draw();
                    }, 500));
                } else {
                    $el.on(eventName, () => {
                        self.saveFiltersToStorage();
                        self.table.draw();
                    });
                }
            }
        });

        $(document).on('click', '.btn-clear-filters', function() {
            self.clearAllFilters();
        });
    }

    initializeEvents() {
        const self = this;

        $(`#${this.config.table.id}`).on('click', '.delete-button', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            self.handleDelete(id);
        });

        // Botones Custom
        if (this.config.customActions) {
            Object.keys(this.config.customActions).forEach(actionName => {
            });
        }

        $(`#${this.config.table.id}`).on('click', '.show-button', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            $(document).trigger('crud:show', [id, self]);
        });

        $(`#${this.config.table.id}`).on('click', '.edit-button', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            $(document).trigger('crud:edit', [id, self]);
        });

        $(document).on('crud:reload', function() {
            self.table.ajax.reload(null, false);
        });
    }

    handleDelete(id) {
        const self = this;
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción eliminará este elemento.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${self.config.urls.urlBase}/${id}`,
                    type: 'DELETE',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        if(response.success) {
                            Swal.fire('Eliminado', response.message, 'success');
                            self.table.draw();
                        } else {
                            Swal.fire('Error', response.message || 'No se pudo eliminar', 'error');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Error al eliminar';
                        if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }
        });
    }
}

// Exponer la clase globalmente
window.CrudTable = CrudTable;
