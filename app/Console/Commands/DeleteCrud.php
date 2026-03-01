<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DeleteCrud extends Command
{
    protected $signature = 'crud:delete {name : Nombre del recurso (ej: Product, Article)}
                            {--model : Eliminar tambien el modelo}
                            {--migration : Eliminar tambien la migracion}
                            {--policy : Eliminar tambien la policy}
                            {--all : Eliminar modelo, migracion, policy, controlador, datatable y vista}
                            {--force : No pedir confirmacion}';

    protected $description = 'Eliminar un CRUD creado con make:crud (Controller + DataTableConfig + Vista + opcionalmente Model, Migration, Policy)';

    protected array $deleted = [];
    protected array $notFound = [];

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));

        $this->warn("Vas a eliminar el CRUD para: {$name}");
        $this->newLine();

        // Mostrar qué se va a eliminar
        $this->showWhatWillBeDeleted($name);

        // Pedir confirmación
        if (!$this->option('force')) {
            if (!$this->confirm('¿Estás seguro de que deseas eliminar estos archivos?', false)) {
                $this->info('Operación cancelada.');
                return self::SUCCESS;
            }
        }

        $this->newLine();

        // 1. Eliminar modelo si se solicita
        if ($this->option('model') || $this->option('all')) {
            $this->deleteModel($name);
        }

        // 2. Eliminar migración si se solicita
        if ($this->option('migration') || $this->option('all')) {
            $this->deleteMigration($name);
        }

        // 3. Eliminar Policy si se solicita
        if ($this->option('policy') || $this->option('all')) {
            $this->deletePolicy($name);
        }

        // 4. Eliminar DataTableConfig (siempre)
        $this->deleteDatatable($name);

        // 5. Eliminar Controller (siempre)
        $this->deleteController($name);

        // 6. Eliminar vista index (siempre)
        $this->deleteIndexView($name);

        // Mostrar resumen
        $this->showSummary();

        return self::SUCCESS;
    }

    protected function showWhatWillBeDeleted(string $name): void
    {
        $kebabPlural = Str::kebab(Str::plural($name));

        $this->line("<fg=yellow>Se eliminarán los siguientes archivos:</>");

        // Siempre se eliminan
        $this->line("  • app/Http/Controllers/{$name}Controller.php");
        $this->line("  • app/DataTables/{$name}DataTableConfig.php");
        $this->line("  • resources/views/{$kebabPlural}/index.blade.php");

        if ($this->option('model') || $this->option('all')) {
            $this->line("  • app/Models/{$name}.php");
        }

        if ($this->option('migration') || $this->option('all')) {
            $tableName = Str::snake(Str::plural($name));
            $this->line("  • database/migrations/*_create_{$tableName}_table.php");
        }

        if ($this->option('policy') || $this->option('all')) {
            $this->line("  • app/Policies/{$name}Policy.php");
            $this->line("  • Entrada en AuthServiceProvider");
        }

        $this->newLine();
    }

    protected function deleteModel(string $name): void
    {
        $path = app_path("Models/{$name}.php");

        if (file_exists($path)) {
            unlink($path);
            $this->deleted[] = "app/Models/{$name}.php";
            $this->info("  ✓ Modelo eliminado");
        } else {
            $this->notFound[] = "app/Models/{$name}.php";
            $this->line("  <fg=gray>Modelo no encontrado, omitiendo...</>");
        }
    }

    protected function deleteMigration(string $name): void
    {
        $tableName = Str::snake(Str::plural($name));
        $migrationsPath = database_path('migrations');
        $pattern = "*_create_{$tableName}_table.php";

        $files = glob("{$migrationsPath}/{$pattern}");

        if (!empty($files)) {
            foreach ($files as $file) {
                unlink($file);
                $this->deleted[] = "database/migrations/" . basename($file);
            }
            $this->info("  ✓ Migración eliminada");
        } else {
            $this->notFound[] = "database/migrations/*_create_{$tableName}_table.php";
            $this->line("  <fg=gray>Migración no encontrada, omitiendo...</>");
        }
    }

    protected function deletePolicy(string $name): void
    {
        $policyName = "{$name}Policy";
        $path = app_path("Policies/{$policyName}.php");

        if (file_exists($path)) {
            unlink($path);
            $this->deleted[] = "app/Policies/{$policyName}.php";
            $this->info("  ✓ Policy eliminada");
        } else {
            $this->notFound[] = "app/Policies/{$policyName}.php";
            $this->line("  <fg=gray>Policy no encontrada, omitiendo...</>");
        }

        // Eliminar del AuthServiceProvider
        $this->removePolicyFromProvider($name);

        // Eliminar directorio Policies si está vacío
        $policiesDir = app_path('Policies');
        if (is_dir($policiesDir) && count(glob("{$policiesDir}/*")) === 0) {
            rmdir($policiesDir);
        }
    }

    protected function removePolicyFromProvider(string $name): void
    {
        $providerPath = app_path('Providers/AuthServiceProvider.php');

        if (!file_exists($providerPath)) {
            return;
        }

        $content = file_get_contents($providerPath);
        $modified = false;

        // Eliminar import del modelo (formato antiguo)
        $modelImport = "use App\\Models\\{$name};\n";
        if (str_contains($content, $modelImport)) {
            $content = str_replace($modelImport, '', $content);
            $modified = true;
        }

        // Eliminar import de la policy (formato antiguo)
        $policyImport = "use App\\Policies\\{$name}Policy;\n";
        if (str_contains($content, $policyImport)) {
            $content = str_replace($policyImport, '', $content);
            $modified = true;
        }

        // Eliminar entrada del array $policies (formato antiguo sin ruta completa)
        $policyEntry = "        {$name}::class => {$name}Policy::class,\n";
        if (str_contains($content, $policyEntry)) {
            $content = str_replace($policyEntry, '', $content);
            $modified = true;
        }

        // Eliminar entrada del array $policies (formato nuevo con ruta completa)
        $policyEntryFull = "        \\App\\Models\\{$name}::class => \\App\\Policies\\{$name}Policy::class,\n";
        if (str_contains($content, $policyEntryFull)) {
            $content = str_replace($policyEntryFull, '', $content);
            $modified = true;
        }

        // También probar sin el salto de línea final (formato antiguo)
        $policyEntryNoNewline = "        {$name}::class => {$name}Policy::class,";
        if (str_contains($content, $policyEntryNoNewline)) {
            $content = str_replace($policyEntryNoNewline, '', $content);
            $modified = true;
        }

        // También probar sin el salto de línea final (formato nuevo)
        $policyEntryFullNoNewline = "        \\App\\Models\\{$name}::class => \\App\\Policies\\{$name}Policy::class,";
        if (str_contains($content, $policyEntryFullNoNewline)) {
            $content = str_replace($policyEntryFullNoNewline, '', $content);
            $modified = true;
        }

        if ($modified) {
            // Limpiar líneas vacías múltiples
            $content = preg_replace("/\n{3,}/", "\n\n", $content);
            file_put_contents($providerPath, $content);
            $this->deleted[] = "Entrada en AuthServiceProvider";
            $this->info("  ✓ Policy eliminada de AuthServiceProvider");
        }
    }

    protected function deleteDatatable(string $name): void
    {
        $datatableName = "{$name}DataTableConfig";
        $path = app_path("DataTables/{$datatableName}.php");

        if (file_exists($path)) {
            unlink($path);
            $this->deleted[] = "app/DataTables/{$datatableName}.php";
            $this->info("  ✓ DataTableConfig eliminado");
        } else {
            $this->notFound[] = "app/DataTables/{$datatableName}.php";
            $this->line("  <fg=gray>DataTableConfig no encontrado, omitiendo...</>");
        }
    }

    protected function deleteController(string $name): void
    {
        $controllerName = "{$name}Controller";
        $path = app_path("Http/Controllers/{$controllerName}.php");

        if (file_exists($path)) {
            unlink($path);
            $this->deleted[] = "app/Http/Controllers/{$controllerName}.php";
            $this->info("  ✓ Controller eliminado");
        } else {
            $this->notFound[] = "app/Http/Controllers/{$controllerName}.php";
            $this->line("  <fg=gray>Controller no encontrado, omitiendo...</>");
        }
    }

    protected function deleteIndexView(string $name): void
    {
        $viewDir = resource_path('views/' . Str::kebab(Str::plural($name)));
        $viewPath = "{$viewDir}/index.blade.php";

        if (file_exists($viewPath)) {
            unlink($viewPath);
            $this->deleted[] = "resources/views/" . Str::kebab(Str::plural($name)) . "/index.blade.php";
            $this->info("  ✓ Vista index eliminada");

            // Eliminar directorio si está vacío
            if (is_dir($viewDir) && count(glob("{$viewDir}/*")) === 0) {
                rmdir($viewDir);
                $this->info("  ✓ Directorio de vistas eliminado (estaba vacío)");
            }
        } else {
            $this->notFound[] = "resources/views/" . Str::kebab(Str::plural($name)) . "/index.blade.php";
            $this->line("  <fg=gray>Vista index no encontrada, omitiendo...</>");
        }
    }

    protected function showSummary(): void
    {
        $this->newLine();
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        if (!empty($this->deleted)) {
            $this->info("Archivos eliminados: " . count($this->deleted));
            foreach ($this->deleted as $file) {
                $this->line("  <fg=green>✓</> {$file}");
            }
        }

        if (!empty($this->notFound)) {
            $this->newLine();
            $this->line("<fg=gray>No encontrados: " . count($this->notFound) . "</>");
        }

        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->newLine();
        $this->warn("Recuerda eliminar manualmente las rutas de routes/web.php si las añadiste.");
    }
}
