# Slotix — Plataforma SaaS de Reservas Multi-Tenant

Sistema de gestión de reservas online construido con Laravel 11. Diseñado para cualquier tipo de negocio que necesite reservas por franjas horarias. Soporta múltiples empresas con bases de datos aisladas por empresa (multi-tenant via stancl/tenancy).

---

## Características

### Vista pública (`/{empresa}`)
- Tira de chips de fechas disponibles según el horario configurado
- Franjas horarias con plazas disponibles en tiempo real (AJAX)
- Formulario de reserva con validación de aforo y antelación mínima
- Confirmación por email con enlace único para gestionar la reserva
- Cancelación de reserva desde el enlace del email
- Añadir reserva a Google Calendar
- Temas visuales por empresa (Neón, Clásico, Pastel)

### Cuentas de usuario
- Registro e inicio de sesión
- `/` redirige al calendario de la empresa del usuario si está autenticado
- Sección "Mis reservas": ver, filtrar por estado/fecha y cancelar reservas
- Añadir cada reserva a Google Calendar

### Panel de administración (`/admin`)
- Dashboard con calendario FullCalendar (mes/semana/día)
- Arrastrar reservas en el calendario para cambiar de fecha (con modal de confirmación y nota del admin)
- Click en reserva abre modal de edición completo: datos, estado, hora, observaciones del cliente y del admin
- Listado de reservas con DataTable (filtros por fecha y estado, búsqueda)
- Confirmar, cancelar y crear reservas manuales
- Configuración de horario: días, apertura/cierre, duración de tramo, aforo, antelación mínima
- Toggle de modo mantenimiento en el topbar
- Campanita de notificaciones (nuevas reservas, cancelaciones, cambios de fecha) con polling cada 60s
- Gestión de empresas (SuperAdmin): crear, editar, activar módulos, migrar BDs
- Sistema de demos: generar accesos temporales a empresas de prueba

### Técnico
- Horas almacenadas en minutos desde medianoche (`630` = 10:30) — sin pérdida de precisión decimal
- Dos campos de notas en reservas: `notas` (cliente) y `notas_admin` (uso interno)
- Emails de confirmación y cancelación con Laravel Mailable
- Notificaciones internas en BD (`admin_notificaciones`) con lectura AJAX
- Middleware de mantenimiento con estado en BD (caché 60s)
- Roles con Spatie Permission: SuperAdmin, Admin, User
- DataTables AJAX con Yajra
- Assets compilados con Vite + SCSS por tema

---

## Arquitectura Multi-Tenant

### Base de datos central
Contiene todos los datos globales:
- `users` — usuarios (SuperAdmin, Admin, User)
- `tenants` — empresas (slug, nombre, logo, tema, colores, activo)
- `empresa_user` — qué usuarios tienen acceso a qué empresas
- `modulos` + `empresa_modulo` — módulos disponibles y activos por empresa
- `admin_notificaciones` — notificaciones para admins
- `demo_invitaciones` — accesos de demo temporales
- Tablas de Spatie (roles, permissions)

### Base de datos por empresa (`empresa_{slug}`)
Cada empresa tiene su propia BD aislada:
- `reservas` — reservas de esa empresa
- `horario_config` — configuración de horario y aforo

### Cómo funciona el contexto de empresa
1. Rutas `/{empresa}/*` — `TenanciaPublica` middleware inicializa tenancy por slug de URL (acceso público sin login).
2. Rutas `/admin/*` y `/mis-reservas` — `EmpresaContext` middleware lee `empresa_id` de sesión e inicializa tenancy.
3. Al terminar el request, `tenancy()->end()` restaura la conexión central.

### Roles

| Rol | Acceso |
|-----|--------|
| **SuperAdmin** | Panel de todas las empresas. Gestión global. |
| **Admin** | Panel de las empresas asignadas. Gestiona reservas, horarios y configuración. |
| **User** | Solo vista pública y `/mis-reservas`. |

### Módulos

| Módulo | Estado |
|--------|--------|
| `reservas` | Activo — gestión de reservas y horario |
| `eventos` | Próximamente |
| `catalogo` | Próximamente |
| `crm` | Próximamente |
| `informes` | Próximamente |

---

## Temas visuales

Cada empresa puede elegir un tema visual desde el panel de administración. Los temas se aplican automáticamente en la vista pública:

| Tema | Descripción |
|------|-------------|
| `neon` | Fondo oscuro cálido con acentos cian y lima neón |
| `clasico` | Estilo limpio y neutro |
| `pastel` | Colores suaves y claros |

---

## Migraciones de empresa

Cuando se añade una tabla o columna a las BDs de tenant:

1. Crear el archivo en `database/migrations/tenant/`
2. Aplicar en todas las empresas:

```bash
# Opción A — Artisan
php artisan tenants:migrate

# Opción B — Panel admin (SuperAdmin)
# Panel → Empresas → "Migrar todas las BDs"
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
git clone https://github.com/JavierSA-dev/slotix.git slotix
cd slotix

# 2. Instalar dependencias
composer install
npm install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Configurar base de datos en .env
# DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 5. Migrar BD central
php artisan migrate

# 6. Seeders iniciales
php artisan db:seed

# 7. Compilar assets
npm run build

# 8. Iniciar servidor
php artisan serve
```

---

## Rutas principales

| Ruta | Descripción |
|------|-------------|
| `/` | Inicio — redirige según rol del usuario autenticado |
| `/{empresa}` | Vista pública de reservas de una empresa |
| `/{empresa}/reservas/{token}` | Detalle/gestión de reserva por token |
| `/login` | Acceso |
| `/mis-reservas` | Reservas del usuario autenticado |
| `/admin` | Dashboard de administración (calendario) |
| `/admin/reservas` | Listado y gestión de reservas |
| `/admin/horario` | Configuración de horario y aforo |
| `/admin/empresas` | Gestión de empresas (SuperAdmin) |

---

## Tecnologías

- **Backend**: Laravel 11, PHP 8.2, Spatie Permission, stancl/tenancy v3
- **Frontend**: Bootstrap 5, jQuery, FullCalendar 6, SweetAlert2, Vite
- **BD**: MySQL / MariaDB (BD central + BD aislada por empresa)
- **Email**: Laravel Mailable (SMTP configurable)
- **Tests**: PHPUnit 10
