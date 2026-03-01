<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class EditCrud extends Command
{
    protected $signature = 'crud:edit {name : Nombre del recurso (ej: Product, Article)}
                            {--add-columns= : Columnas a añadir separadas por coma (ej: price,stock)}
                            {--remove-columns= : Columnas a eliminar separadas por coma (ej: old_field)}
                            {--model : Actualizar también el modelo ($fillable)}
                            {--only-datatable : Solo actualizar el DataTableConfig}
                            {--only-controller : Solo actualizar el Controller}
                            {--only-modal : Solo actualizar el Modal}';

    protected $description = 'Editar un CRUD existente: añadir o eliminar columnas en DataTableConfig, Controller y Modal';

    protected array $filesModified = [];

    protected array $filesNotFound = [];

    protected array $columnDefinitions = [];

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));

        $this->info("Editando CRUD para: {$name}");
        $this->newLine();

        if (! $this->checkFilesExist($name)) {
            return self::FAILURE;
        }

        $currentColumns = $this->detectCurrentColumns($name);

        if (! empty($currentColumns)) {
            $this->line('<fg=cyan>Columnas actuales detectadas:</>');
            foreach ($currentColumns as $col) {
                $this->line("  • {$col}");
            }
            $this->newLine();
        } else {
            $this->warn('  No se pudieron detectar columnas actuales desde el DataTableConfig.');
            $this->newLine();
        }

        $addColumns = $this->getColumnsToAdd($currentColumns);
        $removeColumns = $this->getColumnsToRemove($currentColumns);

        if (empty($addColumns) && empty($removeColumns)) {
            $this->warn('No se especificaron columnas para añadir ni eliminar. Operación cancelada.');

            return self::SUCCESS;
        }

        if (! empty($addColumns)) {
            $this->line('<fg=green>Columnas a añadir:</> '.implode(', ', $addColumns));
        }
        if (! empty($removeColumns)) {
            $this->line('<fg=red>Columnas a eliminar:</> '.implode(', ', $removeColumns));
        }

        $this->newLine();

        if (! $this->confirm('¿Deseas continuar?', true)) {
            $this->info('Operación cancelada.');

            return self::SUCCESS;
        }

        $this->newLine();

        // Preguntar tipo y posición de las nuevas columnas
        $addDefinitions = [];
        if (! empty($addColumns)) {
            $addDefinitions = $this->askColumnTypes($addColumns, $currentColumns);
            $this->newLine();
        }

        // Crear migración ALTER TABLE
        $this->createMigration($name, $addDefinitions, $removeColumns);

        $updateDataTable = ! $this->option('only-controller') && ! $this->option('only-modal');
        $updateController = ! $this->option('only-datatable') && ! $this->option('only-modal');
        $updateModal = ! $this->option('only-datatable') && ! $this->option('only-controller');
        $updateModel = $this->option('model');

        if (! empty($addDefinitions)) {
            if ($updateModel) {
                $this->addColumnsToModel($name, $addDefinitions);
            }
            if ($updateDataTable) {
                $this->addColumnsToDatatable($name, $addDefinitions);
            }
            if ($updateController) {
                $this->addColumnsToController($name, $addDefinitions);
            }
            if ($updateModal) {
                $this->addColumnsToModal($name, $addDefinitions);
            }
        }

        if (! empty($removeColumns)) {
            if ($updateModel) {
                $this->removeColumnsFromModel($name, $removeColumns);
            }
            if ($updateDataTable) {
                $this->removeColumnsFromDatatable($name, $removeColumns);
            }
            if ($updateController) {
                $this->removeColumnsFromController($name, $removeColumns);
            }
            if ($updateModal) {
                $this->removeColumnsFromModal($name, $removeColumns);
            }
        }

        $this->showSummary();

        return self::SUCCESS;
    }

    protected function checkFilesExist(string $name): bool
    {
        $controllerPath = app_path("Http/Controllers/{$name}Controller.php");
        $datatablePath = app_path("DataTables/{$name}DataTableConfig.php");

        $missing = [];

        if (! file_exists($controllerPath)) {
            $missing[] = "app/Http/Controllers/{$name}Controller.php";
        }
        if (! file_exists($datatablePath)) {
            $missing[] = "app/DataTables/{$name}DataTableConfig.php";
        }

        if (! empty($missing)) {
            $this->error('Los siguientes archivos no existen:');
            foreach ($missing as $file) {
                $this->line("  • {$file}");
            }
            $this->newLine();
            $this->line("Usa <fg=yellow>php artisan make:crud {$name}</> para crear el CRUD primero.");

            return false;
        }

        return true;
    }

    protected function detectCurrentColumns(string $name): array
    {
        $datatablePath = app_path("DataTables/{$name}DataTableConfig.php");
        $content = file_get_contents($datatablePath);

        $columns = [];
        if (preg_match("/protected function columns\(\).*?return \[(.*?)\];/s", $content, $matches)) {
            $columnsContent = $matches[1];
            preg_match_all("/Column::make\([^,\n]+,\s*['\"](.+?)['\"]\)/", $columnsContent, $columnMatches);
            foreach ($columnMatches[1] as $field) {
                if ($field !== 'action' && $field !== 'actions') {
                    $columns[] = $field;
                }
            }
        }

        return $columns;
    }

    protected function getColumnsToAdd(array $currentColumns): array
    {
        if ($this->option('add-columns')) {
            $columns = array_map('trim', explode(',', $this->option('add-columns')));
            $columns = array_filter($columns, fn ($col) => ! empty($col));
            $newColumns = array_filter($columns, fn ($col) => ! in_array($col, $currentColumns));

            $duplicates = array_values(array_diff(array_values($columns), array_values($newColumns)));
            if (! empty($duplicates)) {
                $this->warn('Las siguientes columnas ya existen y se omitirán: '.implode(', ', $duplicates));
            }

            return array_values($newColumns);
        }

        if (! $this->confirm('¿Deseas añadir nuevas columnas?', true)) {
            return [];
        }

        $input = $this->ask('Introduce las columnas a añadir separadas por coma (ej: price,stock)');

        if (empty($input)) {
            return [];
        }

        $columns = array_map('trim', explode(',', $input));
        $columns = array_filter($columns, fn ($col) => ! empty($col));
        $newColumns = array_values(array_filter($columns, fn ($col) => ! in_array($col, $currentColumns)));

        $duplicates = array_values(array_diff(array_values($columns), $newColumns));
        if (! empty($duplicates)) {
            $this->warn('Las siguientes columnas ya existen y se omitirán: '.implode(', ', $duplicates));
        }

        return $newColumns;
    }

    protected function getColumnsToRemove(array $currentColumns): array
    {
        if ($this->option('remove-columns')) {
            $columns = array_map('trim', explode(',', $this->option('remove-columns')));

            return array_values(array_filter($columns, fn ($col) => ! empty($col) && in_array($col, $currentColumns)));
        }

        if (empty($currentColumns)) {
            return [];
        }

        if (! $this->confirm('¿Deseas eliminar alguna columna existente?', false)) {
            return [];
        }

        $selected = $this->choice(
            'Selecciona las columnas a eliminar (puedes seleccionar varias separadas por coma)',
            array_merge(['Ninguna'], $currentColumns),
            0,
            null,
            true
        );

        if (in_array('Ninguna', $selected)) {
            return [];
        }

        return $selected;
    }

    // =========================================
    // CONFIGURACIÓN DE COLUMNAS NUEVAS
    // =========================================

    protected function askColumnTypes(array $columns, array $currentColumns = []): array
    {
        $types = [
            'string' => 'string (VARCHAR 255)',
            'text' => 'text (TEXT largo)',
            'integer' => 'integer (INT)',
            'bigInteger' => 'bigInteger (BIGINT)',
            'decimal' => 'decimal (DECIMAL para precios)',
            'float' => 'float (FLOAT)',
            'boolean' => 'boolean (TINYINT 0/1)',
            'date' => 'date (DATE)',
            'datetime' => 'dateTime (DATETIME)',
            'timestamp' => 'timestamp (TIMESTAMP)',
            'json' => 'json (JSON)',
            'foreignId' => 'foreignId (Relación con otra tabla)',
        ];

        $this->line('<fg=cyan>Configuración de columnas nuevas:</>');

        $definitions = [];
        $addedSoFar = [];

        foreach ($columns as $column) {
            $column = trim($column);

            $inferredType = $this->inferMigrationTypeFromName($column);
            $typeKeys = array_keys($types);
            $defaultTypeIndex = array_search($inferredType, $typeKeys);
            $defaultTypeIndex = $defaultTypeIndex !== false ? (int) $defaultTypeIndex : 0;

            $type = $this->choice(
                "  Tipo para '{$column}'",
                array_values($types),
                $defaultTypeIndex
            );

            $typeKey = array_search($type, $types);

            $definition = [
                'name' => $column,
                'type' => $typeKey,
                'nullable' => false,
                'default' => null,
                'after' => null,
            ];

            $definition['nullable'] = $this->confirm("    ¿'{$column}' puede ser NULL?", false);

            if ($typeKey === 'decimal') {
                $definition['precision'] = (int) $this->ask('    Precisión total (ej: 10)', '10');
                $definition['scale'] = (int) $this->ask('    Decimales (ej: 2)', '2');
            }

            if ($typeKey === 'string') {
                $customLength = $this->confirm('    ¿Longitud personalizada? (default: 255)', false);
                if ($customLength) {
                    $definition['length'] = (int) $this->ask('    Longitud', '255');
                }
            }

            if ($typeKey === 'foreignId') {
                $relatedTable = $this->ask('    Tabla relacionada (ej: users, categories)', Str::plural(str_replace('_id', '', $column)));
                $definition['references'] = $relatedTable;
                $definition['onDelete'] = $this->choice('    On Delete', ['cascade', 'set null', 'restrict', 'no action'], 0);
            }

            // Preguntar posición
            $positionOptions = ['Al final'];
            foreach ($currentColumns as $col) {
                $positionOptions[] = "Después de '{$col}'";
            }
            foreach ($addedSoFar as $col) {
                $positionOptions[] = "Después de '{$col}'";
            }

            if (count($positionOptions) > 1) {
                $positionChoice = $this->choice("    ¿Dónde insertar '{$column}'?", $positionOptions, 0);
                if ($positionChoice !== 'Al final') {
                    preg_match("/Después de '(.+)'/", $positionChoice, $posMatches);
                    if (! empty($posMatches[1])) {
                        $definition['after'] = $posMatches[1];
                    }
                }
            }

            $definitions[] = $definition;
            $addedSoFar[] = $column;
        }

        $this->columnDefinitions = $definitions;

        return $definitions;
    }

    // =========================================
    // MIGRACIÓN
    // =========================================

    protected function createMigration(string $name, array $addDefinitions, array $removeColumns): void
    {
        if (empty($addDefinitions) && empty($removeColumns)) {
            return;
        }

        if (! $this->confirm('¿Deseas crear una migración ALTER TABLE para estos cambios?', true)) {
            return;
        }

        $tableName = Str::snake(Str::plural($name));
        $timestamp = date('Y_m_d_His');

        $parts = [];
        if (! empty($addDefinitions)) {
            $cols = implode('_', array_column($addDefinitions, 'name'));
            $parts[] = "add_{$cols}_to";
        }
        if (! empty($removeColumns)) {
            $cols = implode('_', $removeColumns);
            $parts[] = "remove_{$cols}_from";
        }

        $migrationName = implode('_and_', $parts)."_{$tableName}_table";
        if (strlen($migrationName) > 100) {
            $migrationName = "alter_{$tableName}_table";
        }

        $fileName = "{$timestamp}_{$migrationName}.php";
        $path = database_path("migrations/{$fileName}");

        $stub = $this->generateAlterMigrationStub($tableName, $addDefinitions, $removeColumns);
        file_put_contents($path, $stub);
        $this->filesModified[] = "database/migrations/{$fileName}";
        $this->info("  ✓ Migración creada: database/migrations/{$fileName}");
    }

    protected function generateAlterMigrationStub(string $tableName, array $addDefinitions, array $removeColumns): string
    {
        $upAddCode = ! empty($addDefinitions) ? $this->buildColumnsCode($addDefinitions) : '';
        $upDropCode = '';
        $downDropCode = '';

        if (! empty($removeColumns)) {
            if (count($removeColumns) === 1) {
                $upDropCode = "            \$table->dropColumn('{$removeColumns[0]}');";
            } else {
                $dropList = implode("', '", $removeColumns);
                $upDropCode = "            \$table->dropColumn(['{$dropList}']);";
            }
        }

        $downDropColumns = array_column($addDefinitions, 'name');
        if (! empty($downDropColumns)) {
            if (count($downDropColumns) === 1) {
                $downDropCode = "            \$table->dropColumn('{$downDropColumns[0]}');";
            } else {
                $dropList = implode("', '", $downDropColumns);
                $downDropCode = "            \$table->dropColumn(['{$dropList}']);";
            }
        }

        $upParts = array_filter([$upAddCode, $upDropCode]);
        $upCode = implode("\n", $upParts);

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('{$tableName}', function (Blueprint \$table) {
{$upCode}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('{$tableName}', function (Blueprint \$table) {
{$downDropCode}
        });
    }
};
PHP;
    }

    protected function buildColumnsCode(array $definitions): string
    {
        $lines = [];

        foreach ($definitions as $def) {
            $line = '            $table->';

            switch ($def['type']) {
                case 'string':
                    if (isset($def['length'])) {
                        $line .= "string('{$def['name']}', {$def['length']})";
                    } else {
                        $line .= "string('{$def['name']}')";
                    }
                    break;

                case 'decimal':
                    $precision = $def['precision'] ?? 10;
                    $scale = $def['scale'] ?? 2;
                    $line .= "decimal('{$def['name']}', {$precision}, {$scale})";
                    break;

                case 'foreignId':
                    $line .= "foreignId('{$def['name']}')";
                    if ($def['nullable']) {
                        $line .= '->nullable()';
                        $def['nullable'] = false;
                    }
                    if (isset($def['after']) && $def['after']) {
                        $line .= "->after('{$def['after']}')";
                    }
                    $onDelete = $def['onDelete'] ?? 'cascade';
                    $line .= "->constrained('{$def['references']}')->onDelete('{$onDelete}')";
                    $line .= ';';
                    $lines[] = $line;

                    continue 2;

                default:
                    $line .= "{$def['type']}('{$def['name']}')";
            }

            if ($def['nullable']) {
                $line .= '->nullable()';
            }

            if (isset($def['after']) && $def['after']) {
                $line .= "->after('{$def['after']}')";
            }

            $line .= ';';
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    // =========================================
    // AÑADIR COLUMNAS
    // =========================================

    protected function addColumnsToModel(string $name, array $definitions): void
    {
        $path = app_path("Models/{$name}.php");

        if (! file_exists($path)) {
            $this->filesNotFound[] = "app/Models/{$name}.php";

            return;
        }

        $content = file_get_contents($path);

        if (! preg_match("/protected\s+\\\$fillable\s*=\s*\[(.*?)\]/s", $content, $matches)) {
            $this->warn('  ⚠ No se encontró $fillable en el modelo');

            return;
        }

        $fillableContent = $matches[1];
        preg_match_all("/['\"](.+?)['\"]/", $fillableContent, $fieldMatches);
        $existingFields = $fieldMatches[1];

        $modified = false;

        foreach ($definitions as $def) {
            $column = $def['name'];
            if (in_array($column, $existingFields)) {
                continue;
            }

            $after = $def['after'] ?? null;

            if ($after && str_contains($content, "'{$after}'")) {
                $content = preg_replace(
                    "/(['\"]".preg_quote($after, '/')."['\"])/",
                    "$1, '{$column}'",
                    $content,
                    1
                );
            } else {
                $content = preg_replace(
                    "/(protected\s+\\\$fillable\s*=\s*\[)(.*?)(\])/s",
                    "$1$2, '{$column}'$3",
                    $content
                );
            }

            $existingFields[] = $column;
            $modified = true;
        }

        if ($modified) {
            file_put_contents($path, $content);
            $this->filesModified[] = "app/Models/{$name}.php";
            $this->info('  ✓ Modelo actualizado: columnas añadidas a $fillable');
        } else {
            $this->line('  <fg=gray>Modelo: todas las columnas ya estaban en $fillable</>');
        }
    }

    protected function addColumnsToDatatable(string $name, array $definitions): void
    {
        $path = app_path("DataTables/{$name}DataTableConfig.php");

        if (! file_exists($path)) {
            $this->filesNotFound[] = "app/DataTables/{$name}DataTableConfig.php";

            return;
        }

        $content = file_get_contents($path);

        foreach ($definitions as $def) {
            $column = $def['name'];
            $after = $def['after'] ?? null;
            $header = Str::title(str_replace('_', ' ', $column));
            $newLine = "            Column::make('{$header}', '{$column}'),";

            if ($after) {
                $escaped = preg_quote($after, '/');
                $newContent = preg_replace(
                    "/([ \t]*Column::make\([^,\n]+,\s*['\"]".$escaped."['\"]\)[^\n]*\n)/",
                    "$1{$newLine}\n",
                    $content,
                    1
                );
                if ($newContent !== null && $newContent !== $content) {
                    $content = $newContent;

                    continue;
                }
            }

            // Fall back: insertar antes de 'action'
            $newContent = preg_replace(
                "/(\n[ \t]*Column::make\([^,\n]+,\s*['\"]action['\"]\))/",
                "\n{$newLine}$1",
                $content,
                1
            );
            if ($newContent !== null) {
                $content = $newContent;
            }
        }

        file_put_contents($path, $content);
        $this->filesModified[] = "app/DataTables/{$name}DataTableConfig.php";
        $this->info('  ✓ DataTableConfig actualizado: columnas añadidas');
    }

    protected function addColumnsToController(string $name, array $definitions): void
    {
        $path = app_path("Http/Controllers/{$name}Controller.php");

        if (! file_exists($path)) {
            $this->filesNotFound[] = "app/Http/Controllers/{$name}Controller.php";

            return;
        }

        $content = file_get_contents($path);

        if (! preg_match("/->select\((\[.*?\])\)/", $content, $matches)) {
            $this->warn('  ⚠ No se encontró ->select() en el Controller');

            return;
        }

        $selectArray = $matches[1];
        preg_match_all("/['\"](.+?)['\"]/", $selectArray, $colMatches);
        $existingCols = $colMatches[1];

        $currentSelectArray = $selectArray;

        foreach ($definitions as $def) {
            $column = $def['name'];
            if (in_array($column, $existingCols)) {
                continue;
            }

            $after = $def['after'] ?? null;

            if ($after && str_contains($currentSelectArray, "'{$after}'")) {
                $currentSelectArray = preg_replace(
                    "/(['\"]".preg_quote($after, '/')."['\"])/",
                    "$1, '{$column}'",
                    $currentSelectArray,
                    1
                );
            } elseif (str_contains($currentSelectArray, "'created_at'")) {
                $currentSelectArray = str_replace("'created_at'", "'{$column}', 'created_at'", $currentSelectArray);
            } else {
                $currentSelectArray = rtrim($currentSelectArray, ']').", '{$column}']";
            }

            $existingCols[] = $column;
        }

        $newContent = str_replace("->select({$selectArray})", "->select({$currentSelectArray})", $content);

        file_put_contents($path, $newContent);
        $this->filesModified[] = "app/Http/Controllers/{$name}Controller.php";
        $this->info('  ✓ Controller actualizado: columnas añadidas al select');
    }

    protected function addColumnsToModal(string $name, array $definitions): void
    {
        $viewDir = resource_path('views/'.Str::kebab(Str::plural($name)).'/partials');
        $path = "{$viewDir}/modal.blade.php";

        if (! file_exists($path)) {
            $this->filesNotFound[] = 'resources/views/'.Str::kebab(Str::plural($name)).'/partials/modal.blade.php';

            return;
        }

        $content = file_get_contents($path);

        foreach ($definitions as $def) {
            $after = $def['after'] ?? null;
            $fieldHtml = $this->generateSingleFormField($def);

            if ($after) {
                $escaped = preg_quote($after, '/');
                $newContent = preg_replace(
                    '/([ \t]*<div class="mb-3"[^>]*>\n[ \t]*<label[^>]*for="'.$escaped.'"[\s\S]*?<\/div>[ \t]*\n[ \t]*<\/div>[ \t]*\n)/',
                    "$1\n{$fieldHtml}\n",
                    $content,
                    1
                );
                if ($newContent !== null && $newContent !== $content) {
                    $content = $newContent;

                    continue;
                }
            }

            // Fall back: insertar antes de modal-footer
            $newContent = preg_replace(
                '/(\n[ \t]*<\/div>\n[ \t]*<div class="modal-footer">)/',
                "\n{$fieldHtml}$1",
                $content,
                1
            );
            if ($newContent !== null) {
                $content = $newContent;
            }
        }

        file_put_contents($path, $content);
        $this->filesModified[] = 'resources/views/'.Str::kebab(Str::plural($name)).'/partials/modal.blade.php';
        $this->info('  ✓ Modal actualizado: campos añadidos');
    }

    // =========================================
    // ELIMINAR COLUMNAS
    // =========================================

    protected function removeColumnsFromModel(string $name, array $columns): void
    {
        $path = app_path("Models/{$name}.php");

        if (! file_exists($path)) {
            $this->filesNotFound[] = "app/Models/{$name}.php";

            return;
        }

        $content = file_get_contents($path);
        $modified = false;

        foreach ($columns as $column) {
            $escaped = preg_quote($column, '/');
            // Eliminar ", 'column'" o "'column', " del $fillable
            $newContent = preg_replace("/,\s*['\"]".$escaped."['\"]/", '', $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $modified = true;

                continue;
            }
            $newContent = preg_replace("/['\"]".$escaped."['\"]\s*,\s*/", '', $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $modified = true;

                continue;
            }
            // Único elemento del array
            $newContent = preg_replace("/['\"]".$escaped."['\"]/", '', $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $modified = true;
            }
        }

        if ($modified) {
            file_put_contents($path, $content);
            $this->filesModified[] = "app/Models/{$name}.php";
            $this->info('  ✓ Modelo actualizado: columnas eliminadas de $fillable');
        }
    }

    protected function removeColumnsFromDatatable(string $name, array $columns): void
    {
        $path = app_path("DataTables/{$name}DataTableConfig.php");

        if (! file_exists($path)) {
            $this->filesNotFound[] = "app/DataTables/{$name}DataTableConfig.php";

            return;
        }

        $content = file_get_contents($path);
        $modified = false;

        foreach ($columns as $column) {
            $escaped = preg_quote($column, '/');
            $newContent = preg_replace(
                "/\n[ \t]*Column::make\([^,\n]+,\s*['\"]".$escaped."['\"]\)[^\n]*/",
                '',
                $content
            );
            if ($newContent !== $content) {
                $content = $newContent;
                $modified = true;
            }
        }

        if ($modified) {
            file_put_contents($path, $content);
            $this->filesModified[] = "app/DataTables/{$name}DataTableConfig.php";
            $this->info('  ✓ DataTableConfig actualizado: columnas eliminadas');
        } else {
            $this->line('  <fg=gray>DataTableConfig: no se encontraron columnas a eliminar</>');
        }
    }

    protected function removeColumnsFromController(string $name, array $columns): void
    {
        $path = app_path("Http/Controllers/{$name}Controller.php");

        if (! file_exists($path)) {
            $this->filesNotFound[] = "app/Http/Controllers/{$name}Controller.php";

            return;
        }

        $content = file_get_contents($path);
        $modified = false;

        foreach ($columns as $column) {
            $escaped = preg_quote($column, '/');
            $newContent = preg_replace("/,\s*['\"]".$escaped."['\"]/", '', $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $modified = true;

                continue;
            }
            $newContent = preg_replace("/['\"]".$escaped."['\"]\s*,\s*/", '', $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $modified = true;
            }
        }

        if ($modified) {
            file_put_contents($path, $content);
            $this->filesModified[] = "app/Http/Controllers/{$name}Controller.php";
            $this->info('  ✓ Controller actualizado: columnas eliminadas del select');
        } else {
            $this->line('  <fg=gray>Controller: no se encontraron columnas a eliminar</>');
        }
    }

    protected function removeColumnsFromModal(string $name, array $columns): void
    {
        $viewDir = resource_path('views/'.Str::kebab(Str::plural($name)).'/partials');
        $path = "{$viewDir}/modal.blade.php";

        if (! file_exists($path)) {
            $this->filesNotFound[] = 'resources/views/'.Str::kebab(Str::plural($name)).'/partials/modal.blade.php';

            return;
        }

        $content = file_get_contents($path);
        $modified = false;

        foreach ($columns as $column) {
            $escaped = preg_quote($column, '/');
            // Eliminar el bloque <div class="mb-3">...</div> que contenga name="{column}"
            $newContent = preg_replace(
                '/[ \t]*<div class="mb-3">\n[ \t]*<label[^>]*for="'.$escaped.'"[\s\S]*?<\/div>[ \t]*\n[ \t]*<\/div>[ \t]*\n/',
                '',
                $content
            );
            if ($newContent !== $content) {
                $content = $newContent;
                $modified = true;
            }
        }

        if ($modified) {
            file_put_contents($path, $content);
            $this->filesModified[] = 'resources/views/'.Str::kebab(Str::plural($name)).'/partials/modal.blade.php';
            $this->info('  ✓ Modal actualizado: campos eliminados');
        } else {
            $this->line('  <fg=gray>Modal: no se encontraron campos a eliminar</>');
        }
    }

    // =========================================
    // GENERACIÓN DE CAMPOS
    // =========================================

    protected function findColumnDefinition(string $column): ?array
    {
        foreach ($this->columnDefinitions as $def) {
            if ($def['name'] === $column) {
                return $def;
            }
        }

        return null;
    }

    protected function generateSingleFormField(array $def): string
    {
        $column = $def['name'];
        $label = Str::title(str_replace('_', ' ', $column));
        $dbType = $def['type'] ?? $this->inferMigrationTypeFromName($column);
        $nullable = $def['nullable'] ?? false;
        $required = $nullable ? '' : ' required';
        $requiredMark = $nullable ? '' : ' <span class="text-danger">*</span>';

        if ($dbType === 'text' || $dbType === 'json') {
            return <<<BLADE
                    <div class="mb-3">
                        <label for="{$column}" class="form-label">{{ __('{$label}') }}{$requiredMark}</label>
                        <textarea class="form-control" id="{$column}" name="{$column}" rows="3" style="resize:none;"{$required}></textarea>
                        <div class="invalid-feedback" id="{$column}-error"></div>
                    </div>
BLADE;
        }

        if ($dbType === 'integer' || $dbType === 'bigInteger') {
            return <<<BLADE
                    <div class="mb-3">
                        <label for="{$column}" class="form-label">{{ __('{$label}') }}{$requiredMark}</label>
                        <input type="number" class="form-control" id="{$column}" name="{$column}" step="1"{$required}>
                        <div class="invalid-feedback" id="{$column}-error"></div>
                    </div>
BLADE;
        }

        if ($dbType === 'decimal' || $dbType === 'float') {
            return <<<BLADE
                    <div class="mb-3">
                        <label for="{$column}" class="form-label">{{ __('{$label}') }}{$requiredMark}</label>
                        <input type="number" class="form-control" id="{$column}" name="{$column}" step="0.01"{$required}>
                        <div class="invalid-feedback" id="{$column}-error"></div>
                    </div>
BLADE;
        }

        if ($dbType === 'boolean') {
            return <<<BLADE
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="{$column}" name="{$column}" value="1">
                        <label class="form-check-label" for="{$column}">{{ __('{$label}') }}</label>
                        <div class="invalid-feedback" id="{$column}-error"></div>
                    </div>
BLADE;
        }

        if ($dbType === 'date') {
            return <<<BLADE
                    <div class="mb-3">
                        <label for="{$column}" class="form-label">{{ __('{$label}') }}{$requiredMark}</label>
                        <input type="date" class="form-control" id="{$column}" name="{$column}"{$required}>
                        <div class="invalid-feedback" id="{$column}-error"></div>
                    </div>
BLADE;
        }

        if ($dbType === 'datetime' || $dbType === 'timestamp') {
            return <<<BLADE
                    <div class="mb-3">
                        <label for="{$column}" class="form-label">{{ __('{$label}') }}{$requiredMark}</label>
                        <input type="datetime-local" class="form-control" id="{$column}" name="{$column}"{$required}>
                        <div class="invalid-feedback" id="{$column}-error"></div>
                    </div>
BLADE;
        }

        if ($dbType === 'foreignId') {
            return <<<BLADE
                    <div class="mb-3">
                        <label for="{$column}" class="form-label">{{ __('{$label}') }}{$requiredMark}</label>
                        <select class="form-select" id="{$column}" name="{$column}"{$required}>
                            <option value="">{{ __('Selecciona una opción') }}</option>
                            {{-- TODO: Añadir opciones --}}
                        </select>
                        <div class="invalid-feedback" id="{$column}-error"></div>
                    </div>
BLADE;
        }

        $maxlength = isset($def['length']) ? $def['length'] : 255;

        return <<<BLADE
                    <div class="mb-3">
                        <label for="{$column}" class="form-label">{{ __('{$label}') }}{$requiredMark}</label>
                        <input type="text" class="form-control" id="{$column}" name="{$column}" maxlength="{$maxlength}"{$required}>
                        <div class="invalid-feedback" id="{$column}-error"></div>
                    </div>
BLADE;
    }

    protected function generateFormFields(array $columns): string
    {
        $fields = '';
        foreach ($columns as $column) {
            $def = $this->findColumnDefinition($column) ?? ['name' => $column];
            $fields .= $this->generateSingleFormField($def)."\n\n";
        }

        return $fields;
    }

    protected function inferMigrationTypeFromName(string $column): string
    {
        $name = strtolower($column);

        if (str_contains($name, 'description') || str_contains($name, 'content') ||
            str_contains($name, 'body') || str_contains($name, 'notes') ||
            str_contains($name, 'observ') || str_contains($name, 'comment')) {
            return 'text';
        }
        if (str_ends_with($name, '_id')) {
            return 'foreignId';
        }
        if (str_contains($name, 'price') || str_contains($name, 'amount') ||
            str_contains($name, 'total') || str_contains($name, 'cost')) {
            return 'decimal';
        }
        if (str_contains($name, 'quantity') || str_contains($name, 'stock') ||
            str_contains($name, 'count')) {
            return 'integer';
        }
        if (str_contains($name, 'date') || $name === 'fecha') {
            return 'date';
        }
        if (str_starts_with($name, 'is_') || str_starts_with($name, 'has_') ||
            str_contains($name, 'active') || str_contains($name, 'enabled')) {
            return 'boolean';
        }

        return 'string';
    }

    protected function showSummary(): void
    {
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if (! empty($this->filesModified)) {
            $this->info('Archivos modificados: '.count($this->filesModified));
            foreach ($this->filesModified as $file) {
                $this->line("  <fg=green>✓</> {$file}");
            }
        }

        if (! empty($this->filesNotFound)) {
            $this->newLine();
            $this->line('<fg=gray>No encontrados: '.count($this->filesNotFound).'</>');
            foreach ($this->filesNotFound as $file) {
                $this->line("  <fg=gray>•</> {$file}");
            }
        }

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        $this->warn('Recuerda actualizar las rutas y las validaciones (FormRequest) si es necesario.');
    }
}
