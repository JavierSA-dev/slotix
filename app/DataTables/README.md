# DataTables Configuration Layer

Sistema de configuración fluida para crear tablas CRUD con Yajra DataTables de forma rápida y mantenible.

## ¿Qué es esto?

Es un **Fluent Configuration Layer** (Capa de Configuración Fluida) que permite definir tablas DataTables mediante clases PHP en lugar de arrays extensos en los controladores.

---

## Uso Rápido

### 1. Crear una clase de configuración

```php
// app/DataTables/ProductDataTableConfig.php

namespace App\DataTables;

use App\DataTables\Filters\InputFilter;
use App\DataTables\Filters\SelectFilter;

class ProductDataTableConfig extends DataTableConfig
{
    protected function ajaxRoute(): string
    {
        return 'products.getAjax';
    }

    protected function urlBase(): string
    {
        return 'products';
    }

    protected function columns(): array
    {
        return [
            Column::make('Imagen', 'image'),
            Column::make('Nombre', 'name'),
            Column::make('Precio', 'price'),
            Column::make('Stock', 'stock'),
            Column::make(__('Acciones'), 'action')->orderable(false)->searchable(false),
        ];
    }

    protected function filters(): array
    {
        return [
            InputFilter::make('search')
                ->placeholder('Buscar producto...')
                ->style('max-width: 200px;'),

            SelectFilter::make('category')
                ->fromRoute('categories.ajax')
                ->placeholder('Categoría'),
        ];
    }

    protected function actionButtons(): array
    {
        return [
            ActionButton::make('Nuevo Producto')
                ->route('products.create')
                ->success()
                ->icon('fas fa-plus'),
        ];
    }
}
```

### 2. Usar en el controlador

```php
use App\DataTables\ProductDataTableConfig;

class ProductController extends Controller
{
    public function index()
    {
        $config = new ProductDataTableConfig();
        return view('products.index', compact('config'));
    }
}
```

### 3. Usar en la vista

```blade
{{-- resources/views/products/index.blade.php --}}

@extends('layouts.master')

@section('content')
    <x-crud-datatable :config="$config" />
@endsection
```

---

## Referencia de Clases

### Column

Define una columna de la tabla.

```php
use App\DataTables\Column;

// Básico
Column::make('Nombre', 'name')

// Con opciones
Column::make('Precio', 'price')
    ->orderable(false)      // No ordenable
    ->searchable(false)     // No buscable
    ->width('100px')        // Ancho fijo
    ->className('text-end') // Clase CSS
```

| Método | Descripción |
|--------|-------------|
| `make(header, data)` | Crea la columna con título y campo de datos |
| `orderable(bool)` | Permite/deshabilita ordenamiento |
| `searchable(bool)` | Permite/deshabilita búsqueda |
| `width(string)` | Ancho de la columna (ej: '100px', '20%') |
| `className(string)` | Clases CSS para la columna |

---

### InputFilter

Filtro de tipo input de texto.

```php
use App\DataTables\Filters\InputFilter;

InputFilter::make('search')
    ->placeholder('Buscar...')
    ->style('max-width: 180px;')
    ->inputType('number')  // text, number, email, etc.
    ->event('keyup')       // Evento que dispara el filtro
```

| Método | Descripción |
|--------|-------------|
| `make(id)` | Crea el filtro con un ID único |
| `placeholder(string)` | Texto placeholder del input |
| `style(string)` | Estilos CSS inline |
| `inputType(string)` | Tipo de input HTML (default: 'text') |
| `event(string)` | Evento JS que dispara el filtro (default: 'keyup') |

---

### SelectFilter

Filtro de tipo select o select2.

```php
use App\DataTables\Filters\SelectFilter;

// Con opciones estáticas
SelectFilter::make('status')
    ->options([
        1 => 'Activo',
        0 => 'Inactivo',
    ])
    ->placeholder('Estado')
    ->selected(1)  // Valor seleccionado por defecto

// Con opciones desde URL (AJAX)
SelectFilter::make('category')
    ->fromRoute('categories.ajax')  // Carga opciones vía AJAX
    ->placeholder('Categoría')

// Select2 con múltiple selección
SelectFilter::make('tags')
    ->fromRoute('tags.ajax')
    ->multiple()  // Activa select2 con múltiple
    ->placeholder('Selecciona tags')

// Select2 simple (sin múltiple)
SelectFilter::make('brand')
    ->options($brands)
    ->select2()  // Usa select2 para mejor UX
```

| Método | Descripción |
|--------|-------------|
| `make(id)` | Crea el filtro con un ID único |
| `options(array)` | Opciones estáticas [value => label] |
| `url(string)` | URL para cargar opciones vía AJAX |
| `fromRoute(string, array)` | Genera URL desde nombre de ruta |
| `placeholder(string)` | Texto placeholder |
| `selected(mixed)` | Valor seleccionado por defecto |
| `multiple(bool)` | Habilita selección múltiple (activa select2) |
| `select2()` | Usa select2 sin múltiple |
| `style(string)` | Estilos CSS inline |

#### Formato de respuesta AJAX para SelectFilter

El endpoint debe devolver JSON en uno de estos formatos:

```php
// Formato 1: Array de objetos (recomendado)
return response()->json([
    ['id' => 1, 'text' => 'Opción 1'],
    ['id' => 2, 'text' => 'Opción 2'],
]);

// Formato 2: También soporta 'name' o 'label'
return response()->json([
    ['id' => 1, 'name' => 'Opción 1'],
    ['id' => 2, 'label' => 'Opción 2'],
]);

// Formato 3: Objeto clave-valor
return response()->json([
    '1' => 'Opción 1',
    '2' => 'Opción 2',
]);
```

---

### ActionButton

Botones de acción en la cabecera de la tabla.

```php
use App\DataTables\ActionButton;

ActionButton::make('Nuevo Producto')
    ->route('products.create')
    ->success()  // btn-success (verde)
    ->icon('fas fa-plus')

ActionButton::make('Exportar')
    ->url('/products/export')
    ->primary()  // btn-primary (azul)
    ->icon('fas fa-download')
    ->id('exportBtn')

ActionButton::make('Eliminar Seleccionados')
    ->url('#')
    ->danger()  // btn-danger (rojo)
    ->icon('fas fa-trash')
    ->class('btn btn-outline-danger')  // Clase personalizada
```

| Método | Descripción |
|--------|-------------|
| `make(text)` | Crea el botón con texto |
| `url(string)` | URL del enlace |
| `route(string, array)` | Genera URL desde nombre de ruta |
| `id(string)` | ID del elemento HTML |
| `class(string)` | Clases CSS personalizadas |
| `icon(string)` | Clase del icono (FontAwesome, etc.) |
| `success()` | Atajo para `class('btn btn-success')` |
| `primary()` | Atajo para `class('btn btn-primary')` |
| `danger()` | Atajo para `class('btn btn-danger')` |

---

## Configuración Avanzada

### Cambiar ID de tabla o URL de idioma

```php
class ProductDataTableConfig extends DataTableConfig
{
    protected string $tableId = 'products-table';  // Default: 'yajra-datatable'
    protected string $languageUrl = 'build/json/datatable_es-ES.json';

    // ...
}
```

### Extender configuración existente

```php
class AdminUserDataTableConfig extends UserDataTableConfig
{
    protected function columns(): array
    {
        // Añade columnas adicionales a las del padre
        return array_merge(parent::columns(), [
            Column::make('Último Login', 'last_login_at'),
        ]);
    }

    protected function filters(): array
    {
        return array_merge(parent::filters(), [
            SelectFilter::make('active')
                ->options([1 => 'Activo', 0 => 'Inactivo'])
                ->placeholder('Estado'),
        ]);
    }
}
```

---

## Estructura de Archivos

```
app/DataTables/
├── DataTableConfig.php      # Clase base abstracta
├── Column.php               # Definición de columnas
├── ActionButton.php         # Botones de acción
├── Filters/
│   ├── Filter.php           # Clase base de filtros
│   ├── InputFilter.php      # Filtro tipo input
│   └── SelectFilter.php     # Filtro tipo select/select2
├── UserDataTableConfig.php  # Ejemplo: Config de usuarios
└── README.md                # Esta documentación
```

---

## Ejemplo Completo

### Controlador

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\DataTables\ProductDataTableConfig;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function index()
    {
        return view('products.index', [
            'config' => new ProductDataTableConfig()
        ]);
    }

    public function getAjax(Request $request)
    {
        $query = Product::query()->select(['id', 'name', 'price', 'stock', 'image']);

        // Filtro de búsqueda
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filtro de categoría
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        return DataTables::of($query)
            ->addColumn('image', fn($row) => '<img src="' . asset($row->image) . '" width="50">')
            ->addColumn('price', fn($row) => number_format($row->price, 2) . ' €')
            ->addColumn('action', fn($row) => view('products.partials.actions', compact('row')))
            ->rawColumns(['image', 'action'])
            ->make(true);
    }
}
```

### Rutas

```php
// routes/web.php
Route::resource('products', ProductController::class);
Route::get('products/ajax', [ProductController::class, 'getAjax'])->name('products.getAjax');
```

---

## Tips

1. **Nombres de filtros**: El `id` del filtro debe coincidir con el nombre del parámetro que esperas en `getAjax()`

2. **Columnas de acción**: Siempre marca la columna de acciones como no ordenable y no buscable:
   ```php
   Column::make(__('Acciones'), 'action')->orderable(false)->searchable(false)
   ```

3. **Traducciones**: Usa `__()` para textos que necesiten traducción:
   ```php
   Column::make(__('tables.actions'), 'action')
   ActionButton::make(__('buttons.create'))
   ```

4. **Filtros persistentes**: Los filtros se guardan automáticamente en `localStorage` y se restauran al recargar la página.
