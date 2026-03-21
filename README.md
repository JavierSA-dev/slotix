# Minigolf Reservas — Plataforma Multi-Tenant

Sistema de gestión de reservas para minigolf construido con Laravel 11. Soporta múltiples empresas con bases de datos aisladas por empresa (multi-tenant via stancl/tenancy).

---

## Características

### Vista pública (sin cuenta)
- Calendario interactivo para seleccionar fecha (Flatpickr)
- Visualización de franjas horarias disponibles con plazas restantes en tiempo real
- Formulario de reserva con validación de aforo y antelación mínima configurable
- Confirmación por email con enlace único para gestionar la reserva
- Vista de detalle de reserva con opción de cancelar y descarga en PDF

### Cuentas de usuario
- Registro e inicio de sesión con tema visual de minigolf
- Sección "Mis reservas" para usuarios autenticados: ver y cancelar sus reservas

### Panel de administración
- Dashboard con calendario FullCalendar (vista mes/semana) con todas las reservas
- Listado de reservas con filtros por fecha y estado, búsqueda y paginación
- Acciones: confirmar reserva (envía email al cliente), cancelar, crear reserva manual para cualquier persona
- Gestión de horario: días de apertura, hora de apertura/cierre, duración del tramo, aforo máximo por tramo
- Configuración de antelación mínima para reservar y para cancelar (en horas)
- Toggle de modo mantenimiento en el topbar (desactiva la web pública para clientes)

### Técnico
- Franjas horarias representadas como decimales internamente (`10.5` = 10:30)
- Emails de confirmación y cancelación con Mailable de Laravel
- Middleware de mantenimiento con estado en base de datos (caché 60s)
- Roles con Spatie Permission: SuperAdmin, Admin, User
- DataTables AJAX con Yajra
- Assets compilados con Vite

---

## Arquitectura Multi-Tenant

### Base de datos central (`minigolf_reservas`)
Contiene todos los datos globales:
- `users` — usuarios (SuperAdmin, Admin, User)
- `tenants` — empresas (id slug, nombre, logo, colores, activo, en_mantenimiento)
- `empresa_user` — pivot: qué admins tienen acceso a qué empresas
- `modulos` + `empresa_modulo` — módulos disponibles y cuáles están activos por empresa
- Tablas de Spatie (roles, permissions) — globales

### Base de datos por empresa (`empresa_{slug}`)
Cada empresa tiene su propia BD aislada:
- `reservas` — reservas de esa empresa
- `horario_config` — configuración de horario y aforo

### Cómo funciona el contexto de empresa
1. `RequiereEmpresaSeleccionada` middleware: asigna la primera empresa accesible en sesión si no hay ninguna.
2. `EmpresaContext` middleware: lee `empresa_id` de sesión, valida acceso del usuario y llama a `tenancy()->initialize($empresa)` para activar la BD de la empresa.
3. Al terminar el request, `tenancy()->end()` restaura la conexión central.
4. Si hay varias empresas disponibles, el selector en el topbar permite cambiar entre ellas.

### Roles y acceso

| Rol | Acceso |
|-----|--------|
| **SuperAdmin** | Panel de todas las empresas. Puede crear/editar/eliminar empresas, activar módulos y migrar BDs. |
| **Admin** | Panel de las empresas asignadas (via `empresa_user`). Gestiona reservas, horarios y configuración. |
| **User** | Solo `/mis-reservas`. Sin acceso al panel admin. |

### Módulos

Los módulos controlan qué secciones aparecen en el sidebar del admin de cada empresa:

| Módulo | Descripción |
|--------|-------------|
| `reservas` | Gestión de reservas y horario. Activo por defecto en nuevas empresas. |
| `eventos` | Gestión de eventos (futuro). |
| `catalogo` | Catálogo de productos (futuro). |
| `crm` | Gestión de clientes (futuro). |
| `informes` | Informes y estadísticas (futuro). |

Para activar/desactivar módulos: Panel Empresas → botón `🧩` → toggles.

### Conexiones de BD en código

| Conexión | Uso |
|----------|-----|
| `central` | Modelos globales: `User`, `Empresa`, `Modulo`. Siempre apunta a la BD central. |
| `tenant` (dinámica) | Activa durante request admin. Modelos: `Reserva`, `HorarioConfig`. |

Los modelos de datos centrales declaran `protected $connection = 'central'`. Los de tenant no declaran conexión (usan la activa por defecto tras `tenancy()->initialize()`).

La relación `Reserva::user()` usa `.setConnection('central')` para cruzar de la BD tenant → BD central.

---

## Añadir migraciones nuevas a las BDs de empresa

Cuando se añade una nueva tabla o columna a las BDs de tenant:

1. Crear el archivo en `database/migrations/tenant/`
2. Aplicar en todas las empresas existentes:

**Opción A – Panel admin (SuperAdmin):**
Panel Admin → Empresas → botón amarillo **"Migrar todas las BDs"**

**Opción B – Artisan:**
```bash
php artisan tenants:migrate
```

---

## Requisitos

- PHP 8.2+
- Composer
- Node.js + npm
- MySQL / MariaDB

---

## Instalación

```bash
# 1. Clonar el repositorio
git clone <repo-url> minigolf_reservas
cd minigolf_reservas

# 2. Instalar dependencias PHP
composer install

# 3. Instalar dependencias JS
npm install

# 4. Configurar entorno
cp .env.example .env
php artisan key:generate

# 5. Configurar base de datos en .env
# DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 6. Ejecutar migraciones de BD central
php artisan migrate

# 7. Crear módulos y empresa inicial
php artisan db:seed --class=ModuloSeeder
php artisan db:seed --class=EmpresaSeeder

# 8. Migrar datos existentes al tenant #1 (si aplica)
php artisan empresa:migrar-inicial

# 9. Crear usuarios base (SuperAdmin, etc.)
php artisan db:seed

# 10. Compilar assets
npm run build

# 11. Iniciar servidor de desarrollo
php artisan serve
```

---

## Configuración inicial

Tras el seeder, se crea automáticamente:
- **Horario por defecto**: lunes a domingo, 10:00–20:00, tramos de 30 min, aforo de 8 personas.
- **Usuario SuperAdmin**: credenciales definidas en `DatabaseSeeder`.

Accede al panel de administración en `/admin` y ajusta el horario desde **Horario y aforo**.

---

## Rutas principales

| Ruta | Descripción |
|------|-------------|
| `/` | Vista pública de reservas |
| `/reservas/{token}` | Detalle de reserva por token único |
| `/login` | Acceso a cuenta de usuario |
| `/register` | Crear nueva cuenta |
| `/mis-reservas` | Mis reservas (usuarios autenticados) |
| `/admin` | Dashboard de administración |
| `/admin/reservas` | Listado y gestión de reservas |
| `/admin/horario` | Configuración de horario y aforo |

---

## Tecnologías

- **Backend**: Laravel 11, PHP 8.2, Spatie Permission, stancl/tenancy v3
- **Frontend**: Bootstrap 5.3, jQuery, Flatpickr, FullCalendar 6, Vite
- **Base de datos**: MySQL / MariaDB (BD central + BDs por empresa)
- **Email**: Laravel Mailable (configurable con Mailtrap, SMTP, etc.)
- **Tests**: PHPUnit 10, SQLite :memory: con conexión `central` compartida
