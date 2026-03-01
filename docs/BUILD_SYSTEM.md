# Sistema de Build (Vite)

Este proyecto usa **Vite** como bundler para compilar y gestionar los assets front-end.

---

## Comandos disponibles

| Comando | Descripción |
|---------|-------------|
| `npm run dev` | Modo desarrollo con hot-reload |
| `npm run build` | Compila para producción |
| `npm run build-rtl` | Genera versiones RTL de los CSS |

---

## `npm run dev` - Modo Desarrollo

```bash
npm run dev
```

**¿Qué hace?**
- Inicia un servidor Vite en `http://localhost:5173`
- Observa cambios en archivos SCSS/JS
- **Hot Module Replacement (HMR)**: Los cambios se reflejan instantáneamente en el navegador sin recargar

**¿Cuándo usarlo?**
- Mientras desarrollas y modificas estilos o JavaScript
- Necesitas tener este proceso corriendo en una terminal mientras trabajas

**Ejemplo de flujo:**
```bash
# Terminal 1: Servidor Laravel
php artisan serve

# Terminal 2: Servidor Vite (hot-reload)
npm run dev
```

**IMPORTANTE**: En modo dev, las vistas usan el servidor Vite (localhost:5173) para servir los assets. Si cierras `npm run dev`, los estilos y scripts no cargarán correctamente.

---

## `npm run build` - Producción

```bash
npm run build
```

**¿Qué hace?**
1. **Compila SCSS → CSS** minificado
2. **Optimiza JavaScript** (tree-shaking, minificación)
3. **Copia recursos estáticos** a `public/build/`:
   - `resources/libs/` → `public/build/libs/`
   - `resources/images/` → `public/build/images/`
   - `resources/fonts/` → `public/build/fonts/`
   - `resources/json/` → `public/build/json/`
   - `resources/js/` → `public/build/js/`
4. **Genera manifest.json** para que Laravel sepa qué archivos usar

**¿Cuándo usarlo?**
- Antes de subir cambios a producción
- Después de modificar SCSS, JS o añadir nuevas librerías
- Una sola vez, no necesita quedarse corriendo

**Salida generada:**
```
public/build/
├── css/
│   ├── app.min.css           # Estilos de la aplicación
│   ├── bootstrap.min.css     # Bootstrap compilado
│   ├── icons.min.css         # Iconos
│   └── ...
├── js/
│   ├── app.js                # JS principal
│   ├── yajra-datatable.js    # Componente DataTable
│   └── pages/                # Scripts de páginas específicas
├── libs/                     # Librerías de terceros (copiadas)
│   ├── jquery/
│   ├── datatables.net/
│   ├── select2/
│   └── ...
├── images/                   # Imágenes (copiadas)
├── fonts/                    # Fuentes (copiadas)
└── manifest.json             # Mapeo de archivos para Laravel
```

---

## Diferencia entre Dev y Build

| Aspecto | `npm run dev` | `npm run build` |
|---------|---------------|-----------------|
| **Propósito** | Desarrollo | Producción |
| **Proceso** | Continuo (servidor) | Una vez (compila y termina) |
| **Archivos** | Servidos desde memoria | Escritos en `public/build/` |
| **Hot Reload** | ✅ Sí | ❌ No |
| **Minificación** | ❌ No | ✅ Sí |
| **Velocidad carga** | Más lenta | Más rápida |
| **Source Maps** | ✅ Completos | Mínimos |

---

## Estructura de archivos fuente

```
resources/
├── scss/                          # Estilos SCSS
│   ├── app.scss                   # Estilos principales (importa todo)
│   ├── bootstrap.scss             # Bootstrap personalizado
│   ├── icons.scss                 # Iconos (Boxicons, etc.)
│   └── custom/
│       ├── _variables.scss        # Variables personalizables (colores, etc.)
│       ├── components/            # Estilos de componentes
│       ├── pages/                 # Estilos específicos de páginas
│       └── plugins/               # Estilos de plugins
│
├── js/                            # JavaScript
│   ├── app.js                     # JS principal
│   ├── bootstrap.js               # Configuración de axios
│   ├── components/                # Componentes reutilizables
│   │   └── yajra-datatable.js
│   └── pages/                     # Scripts de páginas específicas
│
├── libs/                          # Librerías de terceros
│   ├── jquery/
│   ├── bootstrap/
│   ├── datatables.net/
│   ├── select2/
│   └── ...
│
├── libs-unused/                   # Librerías disponibles pero no activas
│   ├── sweetalert2/
│   ├── tinymce/
│   └── ...
│
├── images/                        # Imágenes
├── fonts/                         # Fuentes
└── json/                          # Archivos JSON
```

---

## Cómo se cargan los assets en las vistas

### Archivos compilados por Vite (SCSS, JS custom)

Usar directiva `@vite()`:

```blade
{{-- En el <head> para CSS --}}
@vite(['resources/scss/app.scss'])

{{-- Antes de </body> para JS --}}
@vite(['resources/js/components/yajra-datatable.js'])
```

### Archivos estáticos copiados (libs, images)

Usar helper `URL::asset()`:

```blade
{{-- Librerías --}}
<script src="{{ URL::asset('build/libs/jquery/jquery.min.js') }}"></script>
<link href="{{ URL::asset('build/libs/select2/css/select2.min.css') }}" rel="stylesheet">

{{-- Imágenes --}}
<img src="{{ URL::asset('build/images/logo.png') }}" alt="Logo">
```

---

## Cómo añadir nuevo JavaScript para tus vistas

Hay tres patrones según el alcance y complejidad del código:

### Patrón 1 — Script inline en la vista (lógica específica de página)

Para inicializaciones, listeners o lógica que solo aplica a una vista concreta. No requiere tocar el build.

```blade
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Tu código aquí
    });
</script>
@endpush
```

El layout base incluye `@stack('scripts')` antes de cerrar `</body>`.

### Patrón 2 — Componente reutilizable procesado por Vite

Para JS que se reutiliza en varias vistas o que usa `import`/módulos ES. Sigue el mismo patrón que `yajra-datatable.js`.

**1. Crea el archivo en `resources/js/components/`:**
```
resources/js/components/mi-componente.js
```

**2. Añádelo al array `input` de [vite.config.js](../vite.config.js):**
```js
input: [
    // ... los existentes
    'resources/js/components/mi-componente.js',
],
```

**3. Cárgalo en Blade con `@vite()`:**
```blade
@vite(['resources/js/components/mi-componente.js'])
```

Vite lo bundleará, minificará y añadirá hash de versión automáticamente.

### Patrón 3 — Script estático copiado sin bundling

Para scripts sin módulos ES que no quieres que Vite procese. Simplemente coloca el archivo en `resources/js/pages/` y se copiará automáticamente a `public/build/js/pages/` al hacer `build`.

```blade
<script src="{{ URL::asset('build/js/pages/mi-script.js') }}"></script>
```

### ¿Cuál usar?

| Situación | Patrón |
|-----------|--------|
| Lógica solo para una vista (init, listeners simples) | Inline con `@push('scripts')` |
| Componente reutilizable entre varias vistas | `resources/js/components/` + entry en `vite.config.js` |
| Script de página sin módulos ES | `resources/js/pages/` (copia estática) |
| Librería de terceros ya minificada | `resources/libs/` → `URL::asset()` |

---

## Añadir una nueva librería

### Si está en NPM:

```bash
npm install nombre-libreria
```

Luego impórtala en tu SCSS o JS, o cópiala manualmente a `resources/libs/`.

### Si es un archivo descargado:

1. Copia la carpeta a `resources/libs/nombre-libreria/`
2. Ejecuta `npm run build` para que se copie a `public/build/libs/`
3. Referénciala en tus vistas:

```blade
<script src="{{ URL::asset('build/libs/nombre-libreria/archivo.js') }}"></script>
```

---

## Recuperar una librería de libs-unused

Si necesitas una librería que está guardada en `libs-unused`:

```bash
# Mover de unused a libs
mv resources/libs-unused/sweetalert2 resources/libs/

# Recompilar
npm run build
```

---

## Solución de problemas

### Los estilos no cargan en desarrollo

```bash
# Asegúrate de tener npm run dev corriendo
npm run dev
```

### Cambios en SCSS no se reflejan

```bash
# Recompila manualmente
npm run build
```

### Error "Vite manifest not found"

```bash
# El manifest.json no existe, ejecuta build
npm run build
```

### Las librerías no se copian

Verifica que están en `resources/libs/` y ejecuta `npm run build`.

---

## Flujo de trabajo recomendado

### Durante desarrollo:

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

### Antes de commit/deploy:

```bash
npm run build
git add public/build
git commit -m "Build assets"
```
