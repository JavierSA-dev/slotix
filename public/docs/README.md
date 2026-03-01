# Taller Empresarial 2.0 - Starter Kit

Bienvenido a la documentación del Starter Kit TE 2.0, la plantilla base para proyectos de Taller Empresarial.

## ¿Qué incluye?

- **Autenticación** — Laravel UI con roles y permisos (Spatie)
- **Jerarquía de roles** — SuperAdmin > Admin > User con restricciones automáticas
- **Datatables** — Componente `<x-crud-datatable>` con filtros, scroll y acciones CRUD
- **Comandos artisan** — `project:init` y `project:release` para automatizar el setup

## Inicio rápido

```bash
# Clonar el repositorio
git clone <url>

# Inicializar el proyecto
php artisan project:init
```

## Publicar una versión

```bash
php artisan project:release
```

El comando te guiará de forma interactiva para:
1. Elegir el tipo de versión (patch / minor / major)
2. Generar el changelog con los commits desde la última versión
3. Crear el commit y el tag git automáticamente

---

[Ver historial de versiones →](/versiones_changelog)
