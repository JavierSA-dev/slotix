# Datatables y Comandos CRUD

Documentación del componente `<x-crud-datatable>` y los generadores artisan para CRUDs.

---

## Componente `<x-crud-datatable>`

El componente encapsula una DataTable de Yajra con filtros, scroll, paginación y acciones CRUD.

### Uso básico en la vista

```blade
<x-crud-datatable :config="$config"></x-crud-datatable>
```

El `$config` se genera desde una clase `DataTableConfig` en el controlador:

```php
use App\DataTables\ProductDataTableConfig;

public function index()
{
    $config = new ProductDataTableConfig();
    return view('products.index', compact('config'));
}
```

### Tipos de filtros disponibles

| Tipo | Descripción |
|------|-------------|
| `input` | Campo de texto libre |
| `select` | Select con opciones estáticas |
| `select2` | Select con Select2 (búsqueda, carga async) |
| `daterange` | Rango de fechas con DateRangePicker |
| `switch` | Toggle on/off |

### Ejemplo de configuración con filtros

```php
// En DataTableConfig:
$this->addFilter(
    InputFilter::make('search')
        ->placeholder('Buscar...')
        ->style('min-width: 200px')
);

$this->addFilter(
    Select2Filter::make('estado')
        ->placeholder('Todos los estados')
        ->options(['activo' => 'Activo', 'inactivo' => 'Inactivo'])
);

$this->addFilter(
    SwitchFilter::make('solo_activos')
        ->label('Solo activos')
        ->defaultOn()
);
```

### Botones de acción

Los botones de acción se añaden a la derecha del header. Soportan botones normales y dropdowns:

```php
$this->addActionButton([
    'text'  => 'Nuevo producto',
    'icon'  => 'bx bx-plus',
    'url'   => route('products.create'),
    'class' => 'btn btn-primary',
]);

// Botón dropdown
$this->addActionButton([
    'text'     => 'Exportar',
    'icon'     => 'bx bx-export',
    'class'    => 'btn btn-secondary dropdown-toggle',
    'dropdown' => true,
    'items'    => [
        ['text' => 'Excel', 'url' => route('products.export.excel')],
        ['text' => 'PDF',   'url' => route('products.export.pdf')],
        ['divider' => true],
        ['text' => 'CSV',   'url' => route('products.export.csv')],
    ],
]);
```

### Eventos JavaScript

```javascript
// Escuchar apertura de modal de edición
$(document).on('crud:edit', function(e, id, table) {
    openProductModal(id);
});

// Escuchar apertura de modal de visualización
$(document).on('crud:show', function(e, id, table) {
    showProduct(id);
});

// Recargar la tabla desde cualquier sitio
$(document).trigger('crud:reload');
```

---

## Generador CRUD (`make:crud`)

Genera un CRUD completo: Controller + DataTableConfig + Vista index.

### Uso

```bash
# CRUD básico
php artisan make:crud Product --columns=name,price,stock

# CRUD completo (incluye Model + Migration + Policy)
php artisan make:crud Product --columns=name,price,stock --all

# Solo con Model
php artisan make:crud Product --columns=name,price,stock --model

# Solo con Migration
php artisan make:crud Product --columns=name,price,stock --migration

# Con Model y Migration
php artisan make:crud Product --columns=name,price,stock --model --migration
```

El comando preguntará de forma interactiva:
- ¿Deseas usar permisos (Policy + Gates)?
- Si sí, la raíz de los permisos (ej: `products`)

### Archivos generados

```
app/Http/Controllers/ProductController.php
app/DataTables/ProductDataTableConfig.php
resources/views/products/index.blade.php
app/Models/Product.php                                  (con --model o --all)
database/migrations/xxxx_create_products_table.php      (con --migration o --all)
app/Policies/ProductPolicy.php                          (si usas permisos)
```

### Características del Controller generado

- Métodos `index`, `store`, `show`, `update`, `destroy`
- `create` y `edit` devuelven JSON (para uso con modales)
- Integración con Policy y permisos opcionales
- Respuestas AJAX con `success/message`

> **Recuerda añadir las rutas manualmente en `routes/web.php`** — el comando muestra las rutas sugeridas al finalizar.

---

## Notas sobre migraciones (`--migration`)

Al usar `make:crud` con `--migration` o `--all`, el comando pregunta por cada columna:

1. **Tipo**: `string`, `text`, `integer`, `decimal`, `boolean`, `date`, `foreignId`, etc.
2. **Nullable**: si puede ser NULL
3. **Configuración específica**:
   - `decimal` → precisión y escala
   - `string` → longitud personalizada
   - `foreignId` → tabla relacionada y acción `onDelete`

### Ejemplo de migración generada

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->decimal('price', 10, 2);
    $table->integer('stock');
    $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
    $table->timestamps();
});
```

---

## Eliminar CRUD (`crud:delete`)

```bash
# Eliminar Controller + DataTable + Vista
php artisan crud:delete Product

# Eliminar también el Model
php artisan crud:delete Product --model

# Eliminar también la Migration
php artisan crud:delete Product --migration

# Eliminar también la Policy
php artisan crud:delete Product --policy

# Eliminar todo
php artisan crud:delete Product --all

# Sin confirmación interactiva
php artisan crud:delete Product --all --force
```

> Las rutas en `routes/web.php` **no se eliminan automáticamente**.

---

## DataTable independiente (`make:datatable`)

Si solo necesitas la configuración DataTable sin el CRUD completo:

```bash
# DataTable básico
php artisan make:datatable Product

# Con modelo específico
php artisan make:datatable ProductList --model=Product

# Con columnas personalizadas
php artisan make:datatable Product --columns=name,price,stock,created_at
```

### Uso en el controlador

```php
use App\DataTables\ProductDataTableConfig;

public function index()
{
    $config = new ProductDataTableConfig();
    return view('products.index', compact('config'));
}
```

### Uso en la vista

```blade
<x-crud-datatable :config="$config"></x-crud-datatable>
```

---

## Generador de Modal (`make:modal`)

Genera modales Bootstrap para crear/editar registros con formularios AJAX:

```bash
# Configuración interactiva
php artisan make:modal Product

# Leer campos desde un DataTableConfig existente
php artisan make:modal Product --from-datatable=Product

# Leer campos desde el $fillable del Model
php artisan make:modal Product --from-model

# Modo rápido (sin preguntas)
php artisan make:modal Product --from-datatable=Product --quick

# Con título personalizado
php artisan make:modal Product --from-model --title="Nuevo Producto"
```

**Archivo generado:** `resources/views/products/partials/modal.blade.php`

### Incluir en la vista index

```blade
<x-crud-datatable :config="$config">
    @include('products.partials.modal')
</x-crud-datatable>
```

### Funciones JS generadas automáticamente

```javascript
openProductModal()        // Abrir en modo creación
openProductModal(data)    // Abrir en modo edición con datos
```

El modal incluye:
- Envío AJAX con validación de errores
- SweetAlert para confirmaciones y notificaciones
- Listener automático para botones `.edit-button`
- Recarga automática de la DataTable tras guardar
