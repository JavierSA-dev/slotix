# Taller Empresarial 2.0 - Starter Kit

Aplicación base Laravel para proyectos de Taller Empresarial.

---

## UPSTREAM — Iniciar un nuevo proyecto desde este Starter Kit (Recomendado)

```bash
# 1. Crea la carpeta local para el nuevo proyecto y clona el Starter Kit
git clone https://github.com/TALLER-EMPRESARIAL-2-0/starter-kit-te20.git [nuevo-proyecto]
cd [nuevo-proyecto]

# 2. Reconfigura los remotes
git remote rename origin upstream
git remote add origin https://github.com/TALLER-EMPRESARIAL-2-0/[nuevo-proyecto].git

# 3. Comprueba los remotes
git remote -v
# origin    https://github.com/.../nuevo-proyecto.git   (fetch/push)
# upstream  https://github.com/.../starter-kit-te20.git (fetch)

# 4. Sube el contenido al nuevo repositorio
git push -u origin main

# 5. Instala las dependencias PHP (necesario para poder usar Artisan)
composer install

# 6. Inicializa el proyecto (configura .env, NPM, BD, git hooks, etc.)
php artisan project:init
```

> **Consejo:** Evita modificar archivos del core del Starter Kit en el proyecto hijo. Hazlos en el Starter Kit y luego tráelos con `git merge upstream/main`.

### Traer mejoras del Starter Kit

```bash
# Ver si hay commits nuevos
git fetch upstream
git log HEAD..upstream/main --oneline

# Ver diferencias con tus archivos
git diff HEAD..upstream/main

# (Opcional) Simular el merge antes de aplicarlo
git merge --no-commit --no-ff upstream/main
git merge --abort   # Cancelar si no convence

# Aplicar las mejoras
git merge upstream/main
git push
```

---

## Inicialización automática

El comando `project:init` configura todo el proyecto de forma interactiva:

```bash
php artisan project:init
```

Te guiará para configurar:
- Nombre de la aplicación y base de datos
- Variables de entorno (`.env`)
- Dependencias Composer y NPM
- Migraciones y seeders
- Git hooks de auto-release
- Laravel Boost (guidelines de IA)

### Opciones del comando

```bash
php artisan project:init --name="Mi App" --db=mi_base_de_datos   # Sin preguntas interactivas
php artisan project:init --skip-npm       # No ejecutar npm install
php artisan project:init --skip-build     # No compilar assets
php artisan project:init --skip-migrate   # No ejecutar migraciones
php artisan project:init --force          # Sobrescribir .env existente
```

---

## Inicialización manual

Si prefieres configurar el proyecto paso a paso:

### 1. Instalar dependencias

```bash
composer install
npm install
```

### 2. Configurar entorno

Copia `.env.example` a `.env` y configura al menos:

```env
APP_NAME=NombreApp
DB_DATABASE=nombre_base_datos
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Generar key y compilar

```bash
php artisan key:generate
npm run build
```

### 4. Base de datos

```bash
# Crear la BD manualmente en MySQL, luego:
php artisan migrate --seed
```

### 5. Instalar git hooks

```bash
cp scripts/hooks/post-commit .git/hooks/post-commit
chmod +x .git/hooks/post-commit
```

### 6. Iniciar servidor

```bash
php artisan serve
```

---

## Credenciales de acceso (entorno de desarrollo)

| Campo    | Valor                              |
|----------|------------------------------------|
| Email    | `desarrollo@tallerempresarial.es`  |
| Password | `iniciotaller` + sufijo de APP_KEY |

---

## Personalización

> **IMPORTANTE**: No modificar archivos en `public/build/` — se sobrescriben con `npm run build`.

### Imágenes y logo

Modifica los ficheros en `resources/images/`:
- Logo de la aplicación
- Imagen de fondo del login

### Colores y estilos

Edita `resources/scss/custom/_variables.scss`:

```scss
// Busca los comentarios //TE2.0 para los estilos principales
$primary:    #556ee6;   // Color primario
$sidebar-bg: #2a3042;   // Fondo del sidebar
```

### JavaScript

Modifica siempre desde `resources/js/`, nunca desde `public/build/`.

---

## Estructura del proyecto

```
├── app/
│   ├── Console/Commands/    # Comandos artisan personalizados
│   ├── DataTables/          # Configuraciones de DataTables
│   ├── Http/Controllers/
│   ├── Policies/            # Autorización
│   ├── Services/            # Lógica de negocio
│   └── Traits/              # Traits reutilizables
├── docs/                    # Documentación técnica
├── resources/
│   ├── images/              # Imágenes personalizables
│   ├── js/                  # JavaScript fuente
│   ├── libs/                # Librerías JS (se copian al build)
│   ├── libs-unused/         # Librerías disponibles pero no activas
│   └── scss/                # Estilos SCSS
├── scripts/hooks/           # Git hooks (instalados por project:init)
├── public/
│   ├── build/               # Assets compilados (no editar)
│   └── docs/                # Documentación navegable en /docs
└── VERSION                  # Versión actual del proyecto
```

---

## Comandos básicos

### Servidor y compilación

```bash
php artisan serve    # Iniciar servidor local
npm run dev          # Compilar con hot-reload
npm run build        # Compilar para producción
```

### Base de datos

```bash
php artisan migrate                  # Ejecutar migraciones pendientes
php artisan migrate:fresh --seed     # Resetear BD y ejecutar seeders
php artisan db:seed                  # Solo seeders
```

### Caché

```bash
php artisan optimize:clear   # Limpiar toda la caché
php artisan config:clear     # Caché de configuración
php artisan route:clear      # Caché de rutas
php artisan view:clear       # Caché de vistas
```

---

## Documentación especializada

| Documento | Contenido |
|-----------|-----------|
| [Datatables y Comandos CRUD](docs/datatables-y-comandos.md) | Componente `<x-crud-datatable>`, generadores `make:crud`, `make:modal`, `make:datatable` |
| [Testing](docs/testing.md) | Suite de tests, cómo ejecutarlos, cómo escribir nuevos |
| [Control de versiones](docs/versionado.md) | Sistema de releases automáticos, git hooks, `project:release` |
| [Sistema de Build](docs/BUILD_SYSTEM.md) | Vite, compilación de assets, añadir librerías |
