# Componente CrudDatatable

## 1. Descripción General
Este componente es un wrapper reutilizable para **Yajra DataTables** en Laravel. Su objetivo es permitir la creación de tablas dinámicas con filtros avanzados (texto, select, select2, rangos de fecha), persistencia de estado (localStorage) y manejo de acciones, todo configurado mediante un único array en PHP.

El componente se encarga de:
- Renderizar la estructura HTML (Filtros a la izquierda, Botones a la derecha).
- Inicializar DataTables via AJAX.
- Manejar la persistencia de filtros automáticamente.
- Normalizar eventos para integración con Modales externos.

## 2. Configuración (`$config`)

Para usar el componente, pasa un array asociativo al componente Blade:
`<x-crud-datatable :config="$config" />`

### Estructura del Array

```php
$config = [
    // 1. URLs (Requerido)
    'urls' => [
        'ajax'    => route('entidad.getAjax'), // Ruta que devuelve el JSON de DataTables
        'idioma'  => asset('json/datatable_es-ES.json'), // Ruta al archivo de traducción
        'data'    => url('entidad'), // URL base para operaciones REST
        'urlBase' => 'entidad' // Prefijo para IDs en llamadas AJAX
    ],

    // 2. Tabla (Requerido)
    'table' => [
        'id' => 'mi-tabla-id', // ID único para el DOM
        'columns' => [
            // Opción A: String simple (header será el nombre capitalizado)
            'nombre',
            // Opción B: Array [Header, data_key]
            ['Fecha Inicio', 'start_date'],
            // Opción C: Array completo (para columnas computadas o custom)
            ['header' => 'Estado', 'data' => 'status_html', 'name' => 'status']
        ]
    ],

    // 3. Filtros (Opcional)
    'filters' => [
        [
            'id' => 'search', 
            'selector' => '#search', // ID del input HTML generado
            'type' => 'input', // 'input', 'select', 'select2'
            'event' => 'keyup', // Evento que dispara la recarga
            'placeholder' => 'Buscar...',
            'style' => 'max-width:180px;'
        ],
        [
            'id' => 'usuario_id',
            'selector' => '#usuario_filter',
            'type' => 'select2',
            'multiple' => true, // Habilita selección múltiple
            'options' => [1 => 'Juan', 2 => 'Maria'], // Array [valor => etiqueta]
            'placeholder' => 'Seleccionar usuarios'
        ],
        [
            'id' => 'rol_id',
            'selector' => '#rol_filter',
            'type' => 'select', 
            'url' => route('api.roles.list'), // Carga opciones vía AJAX
            'placeholder' => 'Selecciona un rol'
        ],
        [
            'id' => 'fechas',
            'selector' => '#date_range',
            'initializer' => 'dateRangePicker', // Activa el plugin DateRangePicker
            'placeholder' => 'Rango de fechas'
        ]
    ],

    // 4. Botones de Acción Superiores (Opcional)
    'actionButtons' => [
        [
            'text' => 'Crear Nuevo',
            'icon' => 'fas fa-plus',
            'class' => 'btn btn-success open-create-modal',
            'url' => '#', // O ruta completa
            'id' => 'btnCreate'
        ]
    ]
];
```

## 3. Tipos de Filtros Soportados

| Tipo | Descripción | Opciones Extra |
|------|-------------|----------------|
| `input` | Input de texto estándar. | `event` (default: keyup con debounce) |
| `select` | Select HTML nativo. | `options` (array), `url` (ajax) |
| `select2` | Select con búsqueda y estilos. | `options`, `url`, `multiple` (bool), `placeholder` |
| `dateRangePicker` | Input con selector de rangos. | Requiere `initializer => 'dateRangePicker'` |

## 4. Sistema de Eventos (Integración con Modales)

El componente JS (`yajra-datatable.js`) emite eventos globales en `$(document)` para desacoplar la lógica de los modales de la tabla.

### Eventos Emitidos por la Tabla
Escucha estos eventos en tu vista principal para abrir tus modales.

**1. Editar Elemento:**
Se dispara al hacer click en un elemento con clase `.edit-button` dentro de la tabla.
```javascript
$(document).on('crud:edit', function(e, id, tableInstance) {
    // Ejemplo:
    $('#modalEditar').modal('show');
    cargarFormularioEditar(id);
});
```

**2. Ver Elemento:**
Se dispara al hacer click en un elemento con clase `.show-button`.
```javascript
$(document).on('crud:show', function(e, id, tableInstance) {
    // Lógica para ver detalles
});
```

### Eventos Recibidos por la Tabla
Dispara estos eventos desde tu código para controlar la tabla externamente.

**1. Recargar Tabla:**
Útil después de guardar un formulario en un modal.
```javascript
// Recarga la tabla manteniendo la paginación actual
$(document).trigger('crud:reload');
```

## 5. Detalles Técnicos

*   **Persistencia:** Los filtros se guardan automáticamente en `localStorage` usando un key único basado en el ID de la tabla.
*   **Debounce:** Los filtros de texto tienen un retraso de 500ms para evitar múltiples llamadas AJAX mientras se escribe.
*   **Manejo de Errores:** Si la sesión expira (401/419), la tabla muestra una alerta amigable y ofrece recargar la página.
