# Control de Versiones

Sistema de releases automáticos del Starter Kit TE 2.0.

La versión actual siempre está visible en el footer de la aplicación y en el fichero `VERSION`.

---

## Flujo de trabajo

### Desarrollo normal

Trabaja y haz commits con el formato habitual:

```bash
git commit -m "feat: nueva funcionalidad"
git commit -m "fix: corrección de bug"
git commit -m "refactor: mejora del código"
```

Es **obligatorio** seguir el estándar **Conventional Commits** para que el commit aparezca en el changelog. Los commits sin prefijo reconocido se ignoran completamente.

| Prefijo | Categoría en changelog |
|---------|------------------------|
| `feat:` | Nuevas funcionalidades |
| `fix:` | Correcciones de errores |
| `perf:` | Mejoras de rendimiento |
| `refactor:` | Refactorizaciones |
| `docs:` | Documentación |
| `test:` | Tests |
| `chore:` | Mantenimiento |
| `style:` | Estilos |
| `ci:` | CI/CD |

También se admite scope y breaking change: `feat(usuarios):`, `fix!:`, `feat(auth)!:`, etc.

### Publicar una versión

Cuando estés en `main` y listo para publicar:

```bash
# Opción A — Automático vía git hook (recomendado)
git commit --allow-empty -m "release:minor"

# Opción B — Comando interactivo con preview
php artisan project:release
```

En ambos casos el sistema:
1. Lee todos los commits desde el último tag
2. Los agrupa por tipo (feat, fix, etc.)
3. Actualiza `VERSION`, `CHANGELOG.md` y `public/docs/versiones_changelog.md`
4. Crea el commit `chore(release): vX.Y.Z` y el tag `vX.Y.Z`

Después, sube al remoto:

```bash
git push && git push --tags
```

---

## Git hook (automatización)

El fichero `scripts/hooks/post-commit` se instala automáticamente en `.git/hooks/` al ejecutar `project:init`.

### Patrones que activan el hook

```bash
git commit --allow-empty -m "release:patch"    # 1.0.0 → 1.0.1
git commit --allow-empty -m "release:minor"    # 1.0.0 → 1.1.0
git commit --allow-empty -m "release:major"    # 1.0.0 → 2.0.0
git commit --allow-empty -m "release:1.5.0"   # versión exacta
```

El commit queda **enmendado en el sitio** como `chore(release): vX.Y.Z`, sin commits extra:

```
* chore(release): v1.1.0  (tag: v1.1.0)   ← resultado limpio
* fix: corrección de bug
* feat: nueva funcionalidad
```

### Protección de rama

El hook **solo se activa en `main` o `master`**. En cualquier otra rama muestra un aviso y no hace nada:

```
⚠️  release:minor ignorado — los releases solo se publican desde 'main'.
    Rama actual: feature/mi-feature
    Fusiona tu rama a main primero y repite el commit de release.
```

### Instalación manual del hook

Si no has ejecutado `project:init`:

```bash
cp scripts/hooks/post-commit .git/hooks/post-commit
chmod +x .git/hooks/post-commit
```

---

## Comando `project:release`

El comando interactivo permite previsualizar el changelog antes de aplicarlo.

```bash
php artisan project:release
```

### Opciones

```bash
php artisan project:release --patch           # 1.0.0 → 1.0.1
php artisan project:release --minor           # 1.0.0 → 1.1.0
php artisan project:release --major           # 1.0.0 → 2.0.0
php artisan project:release --semver=1.5.0    # versión exacta
php artisan project:release --dry-run         # preview sin aplicar cambios
```

### Ejemplo de salida

```
╔═══════════════════════════════════════════════════════════╗
║           TALLER EMPRESARIAL 2.0 - PUBLICAR VERSIÓN       ║
╚═══════════════════════════════════════════════════════════╝

Versión actual: 1.0.0

Tipo de incremento de versión:
  [0] patch  → 1.0.1  (correcciones menores)
  [1] minor  → 1.1.0  (nuevas funcionalidades)
  [2] major  → 2.0.0  (cambios incompatibles)
  [3] custom → Especificar manualmente

┌───────────────────────────────────────────────────────────┐
│  Preview changelog → v1.1.0
└───────────────────────────────────────────────────────────┘

  Nuevas funcionalidades
    • sistema de notificaciones por email (a3f1b2c)
    • exportación a Excel en datatables (d4e5f6a)

  Correcciones de errores
    • paginación incorrecta en móvil (b7c8d9e)

¿Publicar versión v1.1.0? (yes/no) [yes]:
```

---

## Ficheros del sistema de versiones

| Fichero | Descripción |
|---------|-------------|
| `VERSION` | Versión actual en texto plano (`1.1.0\n`) |
| `CHANGELOG.md` | Historial completo en formato Markdown (raíz del proyecto) |
| `public/docs/versiones_changelog.md` | Mismo historial servido en la web (`/docs/#/versiones_changelog`) |
| `scripts/hooks/post-commit` | Script del hook (se instala en `.git/hooks/`) |

### Versión dinámica en el footer

El footer lee la versión de `config('app.version')`, que a su vez lee el fichero `VERSION`:

```php
// config/app.php
'version' => file_exists(base_path('VERSION'))
    ? trim(file_get_contents(base_path('VERSION')))
    : '1.0.0',
```

Si tienes la caché de configuración activa, recuerda limpiarla tras una release:

```bash
php artisan config:clear
```

El comando `project:release` lo hace automáticamente.

---

## Flujo completo con ramas (equipos)

```
feature/mi-feature          main
        │                     │
        ├── feat: cosa A       │
        ├── fix: bug B         │
        │                     │
        └──── merge PR ───────►│
                               │
                    git commit --allow-empty -m "release:minor"
                               │
                    Hook dispara automáticamente:
                    chore(release): v1.1.0  (tag: v1.1.0)
                               │
                    git push && git push --tags
```

**Reglas del equipo:**
- Desarrolla en tu rama con commits normales
- Abre PR → revisión → merge a `main`
- Solo desde `main`: haz el commit de release
- Sube siempre con `git push --tags` para incluir el tag de versión
