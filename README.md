# Minigolf Córdoba — Sistema de Reservas

Sistema de reservas online para un campo de minigolf, construido con Laravel 11.  
Permite a los clientes reservar partidas, gestionar sus citas y al personal administrar el calendario de forma sencilla.

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

# 6. Ejecutar migraciones y seeders
php artisan migrate
php artisan db:seed

# 7. Compilar assets
npm run build

# 8. Iniciar servidor de desarrollo
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

- **Backend**: Laravel 11, PHP 8.2, Spatie Permission
- **Frontend**: Bootstrap 5.3, jQuery, Flatpickr, FullCalendar 6, Vite
- **Base de datos**: MySQL / MariaDB
- **Email**: Laravel Mailable (configurable con Mailtrap, SMTP, etc.)
