# Versiones y Changelog

Historial completo de versiones del Starter Kit TE 2.0.

## [1.3.0] - 25/02/2026

### Nuevas funcionalidades

- Modificación de los CRUDs de usuarios, perfiles y permisos. Eliminación de vistas create, edit y show. Adición de modales para sustituir dichas vistas y agilizar el manejo de estos CRUDs.
- Creación de JS común reutilizable para modales de cara a simplificar código en vistas blade.

### Correcciones de errores

- Arreglado enlace de logo de arriba del sidebar

---

## [1.2.0] - 23/02/2026

### Nuevas funcionalidades

- Adiciones a documentación para generar JS propio. Modificación al comando de publicación de versiones para que te permita lanzar los tests antes de publicar versión.
- Inclusión de nuevas directivas para claude en el claude.md.

### Mantenimiento

- Ahora el comando de versionado no incluye commits sin los prefijos soportados.
- Eliminación de apartados innecesarios del changelog anterior.

---

## [1.1.0] - 2026-02-23

### Nuevas funcionalidades

- Implementación de sistema automático de versionado. (e344399)

### Correcciones de errores

- el comando para actualizar la versión tenía un error. (69401b6)

---

## [1.0.0] - 2026-02-23

### Nuevas funcionalidades

- Implementación inicial del Starter Kit TE 2.0
- Sistema de autenticación con Laravel UI
- Sistema de roles y permisos con jerarquía (SuperAdmin, Admin, User)
- Componente de DataTables con filtros avanzados (select2, daterange, switch)
- Scroll vertical y horizontal sincronizado en tablas
- Comando de inicialización de proyecto (`project:init`)
- Integración de Laravel Boost para desarrollo asistido por IA
- Comando de publicación de versiones (`project:release`)
