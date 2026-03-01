<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeCrud extends Command
{
    protected $signature = 'make:crud {name : Nombre del recurso (ej: Product, Article)}
                            {--columns= : Columnas para la tabla separadas por coma (ej: name,price,stock)}
                            {--model : Crear tambien el modelo}
                            {--migration : Crear tambien la migracion}
                            {--all : Crear modelo, migracion, controlador y datatable}';

    protected $description = 'Crear un CRUD completo con Controller + DataTableConfig + Vista index';

    protected bool $usePermissions = false;

    protected string $permissionRoot = '';

    protected array $columnDefinitions = [];

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));

        // Validar y limpiar columnas
        if ($this->option('columns')) {
            $columns = array_map('trim', explode(',', $this->option('columns')));
            $columns = array_filter($columns, fn ($col) => ! empty($col));

            if (empty($columns)) {
                $this->error('Debe proporcionar al menos una columna válida');

                return self::FAILURE;
            }
        } else {
            $columns = ['name'];
        }

        $this->info("Creando CRUD para: {$name}");
        $this->newLine();

        // Preguntar sobre permisos
        $this->usePermissions = $this->confirm('¿Deseas usar permisos (Policy + Gates)?', false);

        if ($this->usePermissions) {
            $defaultRoot = Str::plural(Str::snake($name));
            $this->permissionRoot = $this->ask(
                '¿Cuál es la raíz de los permisos? (ej: products para products.index, products.create, etc.)',
                $defaultRoot
            );
        }

        // 1. Crear modelo si se solicita
        if ($this->option('model') || $this->option('all')) {
            $this->createModel($name, $columns);
        }

        // 2. Crear migración si se solicita
        if ($this->option('migration') || $this->option('all')) {
            $this->createMigration($name, $columns);
        }

        // 3. Crear Policy si se usan permisos
        if ($this->usePermissions) {
            $this->createPolicy($name);

            // Preguntar si crear permisos en BD
            if ($this->confirm('¿Deseas crear los permisos en la base de datos ahora?', true)) {
                $this->createPermissionsInDatabase($name);
            }
        }

        // 4. Crear DataTableConfig
        $this->createDatatable($name, $columns);

        // 5. Crear Controller con AJAX
        $this->createController($name, $columns);

        // 6. Crear vista index
        $this->createIndexView($name);

        // 7. Crear modal
        $this->createModal($name, $columns);

        // 8. Mostrar rutas sugeridas
        $this->showRoutesSuggestion($name);

        return self::SUCCESS;
    }

    protected function createModel(string $name, array $columns): void
    {
        $path = app_path("Models/{$name}.php");

        if (file_exists($path)) {
            $this->warn('  Modelo ya existe, omitiendo...');

            return;
        }

        $stub = $this->generateModelStub($name, $columns);
        file_put_contents($path, $stub);
        $this->info("  ✓ Modelo creado: app/Models/{$name}.php");
    }

    protected function generateModelStub(string $name, array $columns): string
    {
        $fillable = array_map(fn ($col) => "'".trim($col)."'", $columns);
        $fillableString = implode(', ', $fillable);

        return <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class {$name} extends Model
{
    use HasFactory;

    protected \$fillable = [{$fillableString}];
}
PHP;
    }

    protected function createMigration(string $name, array $columns): void
    {
        $tableName = Str::snake(Str::plural($name));
        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_create_{$tableName}_table.php";
        $path = database_path("migrations/{$fileName}");

        // Preguntar por el tipo de cada columna
        $columnDefinitions = $this->askColumnTypes($columns);

        $stub = $this->generateMigrationStub($tableName, $columnDefinitions);
        file_put_contents($path, $stub);
        $this->info("  ✓ Migración creada: database/migrations/{$fileName}");
    }

    protected function askColumnTypes(array $columns): array
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

        $this->newLine();
        $this->line('<fg=cyan>Configuración de columnas para la migración:</>');

        $definitions = [];
        foreach ($columns as $column) {
            $column = trim($column);

            $type = $this->choice(
                "  Tipo para '{$column}'",
                array_values($types),
                0 // string por defecto
            );

            // Obtener la key del tipo seleccionado
            $typeKey = array_search($type, $types);

            $definition = [
                'name' => $column,
                'type' => $typeKey,
                'nullable' => false,
                'default' => null,
            ];

            // Preguntar si es nullable
            $definition['nullable'] = $this->confirm("    ¿'{$column}' puede ser NULL?", false);

            // Preguntas adicionales según el tipo
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

            $definitions[] = $definition;
        }

        $this->columnDefinitions = $definitions;

        return $definitions;
    }

    protected function generateMigrationStub(string $tableName, array $columnDefinitions): string
    {
        $columnsCode = $this->buildColumnsCode($columnDefinitions);

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
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
{$columnsCode}
            \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
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
                        $def['nullable'] = false; // Ya lo añadimos aquí
                    }
                    $onDelete = $def['onDelete'] ?? 'cascade';
                    $line .= "->constrained('{$def['references']}')->onDelete('{$onDelete}')";
                    break;

                default:
                    $line .= "{$def['type']}('{$def['name']}')";
            }

            if ($def['nullable'] && $def['type'] !== 'foreignId') {
                $line .= '->nullable()';
            }

            if (isset($def['default']) && $def['default'] !== null) {
                $line .= "->default('{$def['default']}')";
            }

            $line .= ';';
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    protected function createPolicy(string $name): void
    {
        $policyName = "{$name}Policy";
        $path = app_path("Policies/{$policyName}.php");

        if (file_exists($path)) {
            $this->warn('  Policy ya existe, omitiendo...');
        } else {
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            $stub = $this->generatePolicyStub($name);
            file_put_contents($path, $stub);
            $this->info("  ✓ Policy creado: app/Policies/{$policyName}.php");
        }

        // Registrar en AuthServiceProvider
        $this->registerPolicyInProvider($name);
    }

    protected function registerPolicyInProvider(string $name): void
    {
        $providerPath = app_path('Providers/AuthServiceProvider.php');

        if (! file_exists($providerPath)) {
            $this->warn('  ⚠ No se encontró AuthServiceProvider.php');
            $this->line('  <fg=yellow>Registra manualmente la Policy en AuthServiceProvider:</>');
            $this->line("  <fg=yellow>\\App\\Models\\{$name}::class => \\App\\Policies\\{$name}Policy::class,</>");

            return;
        }

        $content = file_get_contents($providerPath);

        // Verificar si ya está registrada (con ruta completa o sin ella)
        if (str_contains($content, "\\App\\Models\\{$name}::class") || str_contains($content, "{$name}::class => {$name}Policy::class")) {
            $this->line('  <fg=gray>Policy ya registrada en AuthServiceProvider</>');

            return;
        }

        // Añadir entrada en el array $policies con rutas completas (sin imports)
        $policyEntry = "        \\App\\Models\\{$name}::class => \\App\\Policies\\{$name}Policy::class,";

        // Buscar el array de policies y añadir la nueva entrada
        if (preg_match('/protected \$policies = \[(.*?)\];/s', $content, $matches)) {
            $policiesContent = $matches[1];

            // Añadir la nueva policy antes del cierre del array
            $newPoliciesContent = rtrim($policiesContent);
            if (! empty(trim($newPoliciesContent))) {
                $newPoliciesContent .= "\n{$policyEntry}";
            } else {
                $newPoliciesContent = "\n{$policyEntry}\n    ";
            }

            $content = str_replace(
                "protected \$policies = [{$policiesContent}];",
                "protected \$policies = [{$newPoliciesContent}\n    ];",
                $content
            );

            file_put_contents($providerPath, $content);
            $this->info('  ✓ Policy registrada en AuthServiceProvider');
        }
    }

    protected function createDatatable(string $name, array $columns): void
    {
        $datatableName = "{$name}DataTableConfig";
        $path = app_path("DataTables/{$datatableName}.php");

        if (file_exists($path)) {
            $this->warn('  DataTableConfig ya existe, omitiendo...');

            return;
        }

        $stub = $this->generateDatatableStub($name, $columns);
        file_put_contents($path, $stub);
        $this->info("  ✓ DataTableConfig creado: app/DataTables/{$datatableName}.php");
    }

    protected function createController(string $name, array $columns): void
    {
        $controllerName = "{$name}Controller";
        $path = app_path("Http/Controllers/{$controllerName}.php");

        if (file_exists($path)) {
            $this->warn('  Controller ya existe, omitiendo...');

            return;
        }

        $stub = $this->generateControllerStub($name, $columns);
        file_put_contents($path, $stub);
        $this->info("  ✓ Controller creado: app/Http/Controllers/{$controllerName}.php");
    }

    protected function createIndexView(string $name): void
    {
        $viewDir = resource_path('views/'.Str::kebab(Str::plural($name)));
        $viewPath = "{$viewDir}/index.blade.php";

        if (! is_dir($viewDir)) {
            mkdir($viewDir, 0755, true);
        }

        if (file_exists($viewPath)) {
            $this->warn('  Vista index ya existe, omitiendo...');

            return;
        }

        $stub = $this->generateIndexViewStub($name);
        file_put_contents($viewPath, $stub);
        $this->info('  ✓ Vista creada: resources/views/'.Str::kebab(Str::plural($name)).'/index.blade.php');
    }

    protected function createModal(string $name, array $columns): void
    {
        $viewDir = resource_path('views/'.Str::kebab(Str::plural($name)).'/partials');
        $modalPath = "{$viewDir}/modal.blade.php";

        if (! is_dir($viewDir)) {
            mkdir($viewDir, 0755, true);
        }

        if (file_exists($modalPath)) {
            $this->warn('  Modal ya existe, omitiendo...');

            return;
        }

        $stub = $this->generateModalStub($name, $columns);
        file_put_contents($modalPath, $stub);
        $this->info('  ✓ Modal creado: resources/views/'.Str::kebab(Str::plural($name)).'/partials/modal.blade.php');
    }

    protected function createPermissionsInDatabase(string $name): void
    {
        $this->newLine();
        $this->line('<fg=cyan>Creando permisos en la base de datos...</>');

        // Verificar que Spatie Permission está disponible
        if (! class_exists(\Spatie\Permission\Models\Permission::class)) {
            $this->error('  ✗ Spatie Permission no está instalado');

            return;
        }

        // Limpiar caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos
        $permissions = [
            "{$this->permissionRoot}.index",
            "{$this->permissionRoot}.show",
            "{$this->permissionRoot}.create",
            "{$this->permissionRoot}.edit",
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
            $this->info("  ✓ Permiso creado: {$permission}");
        }

        // Preguntar qué roles deben tener estos permisos
        $availableRoles = \Spatie\Permission\Models\Role::pluck('name')->toArray();

        if (empty($availableRoles)) {
            $this->warn('  ⚠ No hay roles disponibles en la base de datos');
            $this->line('  <fg=yellow>Los permisos se han creado pero no se han asignado a ningún rol</>');

            return;
        }

        $this->newLine();
        $roleOptions = array_merge(['Ninguno', 'Todos'], $availableRoles);

        // Buscar índice de SuperAdmin para usarlo como default
        $defaultIndex = array_search('SuperAdmin', $roleOptions);
        if ($defaultIndex === false) {
            $defaultIndex = 0; // Si no existe SuperAdmin, usar 'Ninguno' como default
        }

        $selectedRoles = $this->choice(
            '¿Qué roles deben tener acceso a '.$name.'? (separados por coma)',
            $roleOptions,
            $defaultIndex,
            null,
            true
        );

        if (! in_array('Ninguno', $selectedRoles)) {
            if (in_array('Todos', $selectedRoles)) {
                $selectedRoles = $availableRoles;
            }

            foreach ($selectedRoles as $roleName) {
                if ($roleName === 'Ninguno' || $roleName === 'Todos') {
                    continue;
                }

                $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
                if ($role) {
                    $role->givePermissionTo($permissions);
                    $this->info("  ✓ Permisos asignados al rol: {$roleName}");
                }
            }
        }

        $this->newLine();
        $this->line('  <fg=green>Permisos creados y asignados correctamente</>');
    }

    protected function showRoutesSuggestion(string $name): void
    {
        $kebabPlural = Str::kebab(Str::plural($name));
        $controllerName = "{$name}Controller";

        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line('<fg=green>Añade estas rutas a routes/web.php:</>');
        $this->newLine();
        $this->line("<fg=yellow>use App\\Http\\Controllers\\{$controllerName};</>");
        $this->newLine();

        if ($this->usePermissions) {
            $this->line("<fg=yellow>// {$name} (con permisos)</>");
            $this->line("<fg=yellow>Route::middleware(['permission:{$this->permissionRoot}.index'])->group(function () {</>");
            $this->line("<fg=yellow>    Route::get('{$kebabPlural}/get-ajax', [{$controllerName}::class, 'getAjax'])->name('{$kebabPlural}.get-ajax');</>");
            $this->line("<fg=yellow>    Route::resource('{$kebabPlural}', {$controllerName}::class);</>");
            $this->line('<fg=yellow>});</>');
        } else {
            $this->line("<fg=yellow>// {$name}</>");
            $this->line("<fg=yellow>Route::get('{$kebabPlural}/get-ajax', [{$controllerName}::class, 'getAjax'])->name('{$kebabPlural}.get-ajax');</>");
            $this->line("<fg=yellow>Route::resource('{$kebabPlural}', {$controllerName}::class);</>");
        }

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($this->usePermissions) {
            $this->newLine();
            $this->line('<fg=green>Permisos requeridos:</>');
            $this->line("<fg=cyan>  - {$this->permissionRoot}.index</>");
            $this->line("<fg=cyan>  - {$this->permissionRoot}.show</>");
            $this->line("<fg=cyan>  - {$this->permissionRoot}.create</>");
            $this->line("<fg=cyan>  - {$this->permissionRoot}.edit</>");
            $this->newLine();
            $this->line('<fg=yellow>Si no creaste los permisos en la BD, añádelos al PermissionSeeder:</>');
            $this->line('<fg=yellow>database/seeders/PermissionSeeder.php</>');
            $this->newLine();
            $this->line('<fg=gray>// Agregar en PermissionSeeder.php:</>');
            $this->line('<fg=gray>$this->createPermissions([</>');
            $this->line("<fg=gray>    '{$this->permissionRoot}.index',</>");
            $this->line("<fg=gray>    '{$this->permissionRoot}.create',</>");
            $this->line("<fg=gray>    '{$this->permissionRoot}.show',</>");
            $this->line("<fg=gray>    '{$this->permissionRoot}.edit',</>");
            $this->line("<fg=gray>], '{$name}', ['SuperAdmin']);</>");
            $this->newLine();
            $this->line('<fg=yellow>Luego ejecuta: php artisan db:seed --class=PermissionSeeder</>');
        }

        $this->newLine();
        $this->line('<fg=red>⚠ IMPORTANTE:</>');
        $this->line('<fg=yellow>Si ya has iniciado sesión, CIERRA SESIÓN Y VUELVE A INICIARLA</>');
        $this->line('<fg=yellow>para que los permisos se carguen correctamente en tu sesión.</>');
    }

    protected function generatePolicyStub(string $name): string
    {
        $modelVar = Str::camel($name);

        return <<<PHP
<?php

namespace App\Policies;

use App\Models\\{$name};
use App\Models\User;

class {$name}Policy
{
    /**
     * Ejecutar antes de cualquier otro check.
     * Si devuelve true/false, se usa ese resultado.
     * Si devuelve null, continua con el metodo especifico.
     */
    public function before(User \$user, string \$ability): ?bool
    {
        // SuperAdmin tiene acceso a todo
        if (\$user->hasRole('SuperAdmin')) {
            return true;
        }

        return null;
    }

    /**
     * Determina si el usuario puede ver el listado.
     * Metodo: index()
     */
    public function viewAny(User \$user): bool
    {
        return \$user->hasPermissionTo('{$this->permissionRoot}.index');
    }

    /**
     * Determina si el usuario puede ver un registro especifico.
     * Metodo: show()
     */
    public function view(User \$user, {$name} \${$modelVar}): bool
    {
        return \$user->hasPermissionTo('{$this->permissionRoot}.show');
    }

    /**
     * Determina si el usuario puede crear registros.
     * Metodos: create(), store()
     */
    public function create(User \$user): bool
    {
        return \$user->hasPermissionTo('{$this->permissionRoot}.create');
    }

    /**
     * Determina si el usuario puede actualizar un registro.
     * Metodos: edit(), update()
     */
    public function update(User \$user, {$name} \${$modelVar}): bool
    {
        return \$user->hasPermissionTo('{$this->permissionRoot}.edit');
    }

    /**
     * Determina si el usuario puede eliminar un registro.
     * Metodo: destroy()
     */
    public function delete(User \$user, {$name} \${$modelVar}): bool
    {
        return \$user->hasPermissionTo('{$this->permissionRoot}.edit');
    }
}
PHP;
    }

    protected function generateDatatableStub(string $name, array $columns): string
    {
        $urlBase = Str::kebab(Str::plural($name));
        $ajaxRoute = "{$urlBase}.get-ajax";

        $columnsCode = '';
        foreach ($columns as $column) {
            $column = trim($column);
            $header = Str::title(str_replace('_', ' ', $column));
            $columnsCode .= "            Column::make('{$header}', '{$column}'),\n";
        }

        if ($this->usePermissions) {
            return <<<PHP
<?php

namespace App\DataTables;

use App\DataTables\Filters\InputFilter;
use App\Models\\{$name};
use Illuminate\Support\Facades\Gate;

class {$name}DataTableConfig extends DataTableConfig
{
    protected function ajaxRoute(): string
    {
        return '{$ajaxRoute}';
    }

    protected function urlBase(): string
    {
        return '{$urlBase}';
    }

    protected function columns(): array
    {
        return [
{$columnsCode}            Column::make(__('Acciones'), 'action')->orderable(false)->searchable(false)->className('text-center'),
        ];
    }

    protected function filters(): array
    {
        return [
            InputFilter::make('search')
                ->placeholder('Buscar...')
                ->style('max-width:180px;'),
        ];
    }

    protected function actionButtons(): array
    {
        \$buttons = [];

        if (Gate::allows('create', {$name}::class)) {
            \$buttons[] = ActionButton::make(__('Crear {$name}'))
                ->url('#')
                ->id('create{$name}Button')
                ->primary()
                ->icon('fas fa-plus');
        }

        return \$buttons;
    }
}
PHP;
        }

        return <<<PHP
<?php

namespace App\DataTables;

use App\DataTables\Filters\InputFilter;

class {$name}DataTableConfig extends DataTableConfig
{
    protected function ajaxRoute(): string
    {
        return '{$ajaxRoute}';
    }

    protected function urlBase(): string
    {
        return '{$urlBase}';
    }

    protected function columns(): array
    {
        return [
{$columnsCode}            Column::make(__('Acciones'), 'action')->orderable(false)->searchable(false)->className('text-center'),
        ];
    }

    protected function filters(): array
    {
        return [
            InputFilter::make('search')
                ->placeholder('Buscar...')
                ->style('max-width:180px;'),
        ];
    }

    protected function actionButtons(): array
    {
        return [
            ActionButton::make(__('Crear {$name}'))
                ->url('#')
                ->id('create{$name}Button')
                ->primary()
                ->icon('fas fa-plus'),
        ];
    }
}
PHP;
    }

    protected function generateControllerStub(string $name, array $columns): string
    {
        $urlBase = Str::kebab(Str::plural($name));
        $variable = Str::camel($name);
        $variablePlural = Str::camel(Str::plural($name));
        // Nombre del parámetro de la ruta (singular, snake_case) para authorizeResource
        $parameterName = Str::snake($name);

        $columnsSelect = "['id', '".implode("', '", array_map('trim', $columns))."', 'created_at']";

        $searchColumns = array_slice($columns, 0, 2);
        $searchCode = '';
        if (! empty($searchColumns)) {
            $first = trim($searchColumns[0]);
            $searchCode = "\$query->where('{$first}', 'like', \"%{\$search}%\")";
            if (isset($searchColumns[1])) {
                $second = trim($searchColumns[1]);
                $searchCode .= "\n                      ->orWhere('{$second}', 'like', \"%{\$search}%\")";
            }
            $searchCode .= ';';
        }

        if ($this->usePermissions) {
            return $this->generateControllerWithPermissions($name, $columns, $urlBase, $variable, $parameterName, $columnsSelect, $searchCode);
        }

        return $this->generateControllerWithoutPermissions($name, $columns, $urlBase, $variable, $parameterName, $columnsSelect, $searchCode);
    }

    protected function generateControllerWithPermissions(string $name, array $columns, string $urlBase, string $variable, string $parameterName, string $columnsSelect, string $searchCode): string
    {
        return <<<PHP
<?php

namespace App\Http\Controllers;

use App\Models\\{$name};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;
use App\DataTables\\{$name}DataTableConfig;

class {$name}Controller extends Controller
{
    /**
     * IMPORTANTE: Este controlador está configurado para trabajar con MODALES.
     * - Los métodos create() y edit() retornan JSON (no vistas).
     * - El frontend debe capturar estos datos y mostrarlos en un modal.
     * - El botón de editar tiene la clase 'btn-edit' para manejarlo con JavaScript.
     */

    public function __construct()
    {
        // Autoriza automaticamente todos los metodos del resource EXCEPTO edit (que recibe \$id, no el modelo)
        // Mapeo: index->viewAny, show->view, create/store->create, update->update, destroy->delete
        // IMPORTANTE: El segundo parámetro debe coincidir con el nombre del parámetro de la ruta (singular)
        \$this->authorizeResource({$name}::class, '{$parameterName}', [
            'except' => ['edit', 'destroy']
        ]);
    }

    public function index(): View
    {
        \$config = new {$name}DataTableConfig();
        return view('{$urlBase}.index', compact('config'));
    }

    public function getAjax(Request \$request)
    {
        // Autorizar el acceso al listado (equivalente a index/viewAny)
        \$this->authorize('viewAny', {$name}::class);

        \$query = {$name}::query()->select({$columnsSelect});

        // Filtro de búsqueda
        if (\$request->filled('search')) {
            \$search = \$request->input('search');
            {$searchCode}
        }

        return DataTables::of(\$query)
            ->addIndexColumn()
            ->addColumn('action', function (\$row) {
                \$btn = '<div class="d-flex gap-1 justify-content-center">';

                // Boton Ver
                if (Gate::allows('view', \$row)) {
                    \$btn .= '<a href="' . route('{$urlBase}.show', \$row->id) . '" class="btn btn-sm btn-primary" title="' . __('botones.Ver') . '"><i class="fa fa-eye"></i></a>';
                }

                // Boton Editar
                if (Gate::allows('update', \$row)) {
                    \$btn .= '<button type="button" class="btn btn-sm btn-success btn-edit" data-url="' . route('{$urlBase}.edit', \$row->id) . '" title="' . __('botones.Editar') . '"><i class="fa fa-edit"></i></button>';
                }

                // Boton Eliminar
                if (Gate::allows('delete', \$row)) {
                    \$btn .= '<button type="button" class="btn btn-danger btn-sm delete-button" data-id="' . \$row->id . '" title="' . __('botones.Eliminar') . '"><i class="fa fa-trash"></i></button>';
                }

                \$btn .= '</div>';
                return \$btn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Retorna datos para el modal de creación.
     * Este método NO renderiza una vista, retorna JSON para ser consumido por JavaScript.
     */
    public function create(): JsonResponse
    {
        \${$variable} = new {$name}();
        return response()->json([
            'success' => true,
            'data' => \${$variable}
        ]);
    }

    public function store(Request \$request): JsonResponse
    {
        try {
            // TODO: Crear FormRequest para validación
            \${$variable} = {$name}::create(\$request->all());

            return response()->json([
                'success' => true,
                'message' => '{$name} ' . __('messages.creado'),
                'data' => \${$variable}
            ], 201);
        } catch (\Exception \$e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error_crear') . ': ' . \$e->getMessage()
            ], 500);
        }
    }

    public function show(\$id): View
    {
        \${$variable} = {$name}::findOrFail(\$id);
        return view('{$urlBase}.show', compact('{$variable}'));
    }

    /**
     * Retorna datos para el modal de edición.
     * Este método NO renderiza una vista, retorna JSON para ser consumido por JavaScript.
     */
    public function edit(\$id): JsonResponse
    {
        \${$variable} = {$name}::findOrFail(\$id);
        \$this->authorize('update', \${$variable});

        return response()->json([
            'success' => true,
            'data' => \${$variable}
        ]);
    }

    public function update(Request \$request, {$name} \${$parameterName}): JsonResponse
    {
        try {
            // TODO: Crear FormRequest para validación
            \${$parameterName}->update(\$request->all());

            return response()->json([
                'success' => true,
                'message' => '{$name} ' . __('messages.actualizado'),
                'data' => \${$parameterName}
            ]);
        } catch (\Exception \$e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error_actualizar') . ': ' . \$e->getMessage()
            ], 500);
        }
    }

    public function destroy(\$id): JsonResponse
    {
        try {
            \${$variable} = {$name}::findOrFail(\$id);
            \$this->authorize('update', \${$variable});
            \${$variable}->delete();

            return response()->json([
                'success' => true,
                'message' => '{$name} ' . __('messages.eliminado')
            ]);
        } catch (\Exception \$e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error_eliminar') . ': ' . \$e->getMessage()
            ], 500);
        }
    }
}
PHP;
    }

    protected function generateControllerWithoutPermissions(string $name, array $columns, string $urlBase, string $variable, string $parameterName, string $columnsSelect, string $searchCode): string
    {
        return <<<PHP
<?php

namespace App\Http\Controllers;

use App\Models\\{$name};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;
use App\DataTables\\{$name}DataTableConfig;

class {$name}Controller extends Controller
{
    /**
     * IMPORTANTE: Este controlador está configurado para trabajar con MODALES.
     * - Los métodos create() y edit() retornan JSON (no vistas).
     * - El frontend debe capturar estos datos y mostrarlos en un modal.
     * - El botón de editar tiene la clase 'btn-edit' para manejarlo con JavaScript.
     */

    public function index(): View
    {
        \$config = new {$name}DataTableConfig();
        return view('{$urlBase}.index', compact('config'));
    }

    public function getAjax(Request \$request)
    {
        \$query = {$name}::query()->select({$columnsSelect});

        // Filtro de búsqueda
        if (\$request->filled('search')) {
            \$search = \$request->input('search');
            {$searchCode}
        }

        return DataTables::of(\$query)
            ->addIndexColumn()
            ->addColumn('action', function (\$row) {
                \$btn = '<div class="d-flex gap-1 justify-content-center">';

                \$btn .= '<a href="' . route('{$urlBase}.show', \$row->id) . '" class="btn btn-sm btn-primary" title="' . __('botones.Ver') . '"><i class="fa fa-eye"></i></a>';
                \$btn .= '<button type="button" class="btn btn-sm btn-success btn-edit" data-url="' . route('{$urlBase}.edit', \$row->id) . '" title="' . __('botones.Editar') . '"><i class="fa fa-edit"></i></button>';

                \$btn .= '<button type="button" class="btn btn-danger btn-sm delete-button" data-id="' . \$row->id . '" title="' . __('botones.Eliminar') . '"><i class="fa fa-trash"></i></button>';

                \$btn .= '</div>';
                return \$btn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Retorna datos para el modal de creación.
     * Este método NO renderiza una vista, retorna JSON para ser consumido por JavaScript.
     */
    public function create(): JsonResponse
    {
        \${$variable} = new {$name}();
        return response()->json([
            'success' => true,
            'data' => \${$variable}
        ]);
    }

    public function store(Request \$request): JsonResponse
    {
        try {
            // TODO: Crear FormRequest para validación
            \${$variable} = {$name}::create(\$request->all());

            return response()->json([
                'success' => true,
                'message' => '{$name} ' . __('messages.creado'),
                'data' => \${$variable}
            ], 201);
        } catch (\Exception \$e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error_crear') . ': ' . \$e->getMessage()
            ], 500);
        }
    }

    public function show(\$id): View
    {
        \${$variable} = {$name}::findOrFail(\$id);
        return view('{$urlBase}.show', compact('{$variable}'));
    }

    /**
     * Retorna datos para el modal de edición.
     * Este método NO renderiza una vista, retorna JSON para ser consumido por JavaScript.
     */
    public function edit(\$id): JsonResponse
    {
        \${$variable} = {$name}::findOrFail(\$id);

        // Sin autorización automática porque no hay authorizeResource en esta versión

        return response()->json([
            'success' => true,
            'data' => \${$variable}
        ]);
    }

    public function update(Request \$request, {$name} \${$parameterName}): JsonResponse
    {
        try {
            // TODO: Crear FormRequest para validación
            \${$parameterName}->update(\$request->all());

            return response()->json([
                'success' => true,
                'message' => '{$name} ' . __('messages.actualizado'),
                'data' => \${$parameterName}
            ]);
        } catch (\Exception \$e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error_actualizar') . ': ' . \$e->getMessage()
            ], 500);
        }
    }

    public function destroy(\$id): JsonResponse
    {
        try {
            {$name}::findOrFail(\$id)->delete();

            return response()->json([
                'success' => true,
                'message' => '{$name} ' . __('messages.eliminado')
            ]);
        } catch (\Exception \$e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error_eliminar') . ': ' . \$e->getMessage()
            ], 500);
        }
    }
}
PHP;
    }

    protected function generateIndexViewStub(string $name): string
    {
        $title = Str::title(Str::plural($name));
        $viewPath = Str::kebab(Str::plural($name));

        return <<<BLADE
@extends('layouts.master')

@section('title')
    {$title}
@endsection

@section('content')
    <x-crud-datatable :config="\$config"></x-crud-datatable>

    {{-- Incluir el modal --}}
    @include('{$viewPath}.partials.modal')
@endsection
BLADE;
    }

    protected function generateModalStub(string $name, array $columns): string
    {
        $variable = Str::camel($name);
        $urlBase = Str::kebab(Str::plural($name));
        $modalId = Str::kebab($name).'-modal';
        $formId = $variable.'Form';
        $functionName = 'open'.$name.'Modal';

        // Generar campos del formulario
        $formFields = $this->generateFormFields($columns);

        return <<<BLADE
{{-- Modal Nuevo/Editar {$name} --}}
<div class="modal fade" id="{$modalId}" tabindex="-1" aria-labelledby="{$modalId}-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{$modalId}-label">{{ __('Nuevo {$name}') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="{$formId}">
                <div class="modal-body">
                    {{-- Campo oculto para el ID (edición) --}}
                    <input type="hidden" id="{$variable}_id" name="id">

{$formFields}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('style')
<link rel="stylesheet" href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}">
@endpush

@push('scripts')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('{$modalId}');
    const form = document.getElementById('{$formId}');
    const modalTitle = modal.querySelector('.modal-title');
    const modalInstance = new bootstrap.Modal(modal);
    let isEditMode = false;

    // Limpiar formulario al cerrar el modal
    modal.addEventListener('hidden.bs.modal', function() {
        form.reset();
        form.querySelector('[name="id"]').value = '';
        form.classList.remove('was-validated');
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        isEditMode = false;
    });

    // Envío del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const id = form.querySelector('[name="id"]').value;
        isEditMode = id !== '';

        const url = isEditMode
            ? `{{ url('{$urlBase}') }}/\${id}`
            : '{{ route("{$urlBase}.store") }}';

        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        // Limpiar mensajes de error previos
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        fetch(url, {
            method: isEditMode ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                modalInstance.hide();
                // Recargar DataTable
                if (typeof dataTable !== 'undefined') {
                    dataTable.ajax.reload(null, false);
                } else if (\$.fn.DataTable && \$('.dataTable').length) {
                    \$('.dataTable').DataTable().ajax.reload(null, false);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Ha ocurrido un error'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);

            // Manejar errores de validación
            if (error.errors) {
                Object.keys(error.errors).forEach(field => {
                    const input = form.querySelector(`[name="\${field}"]`);
                    const errorDiv = document.getElementById(`\${field}-error`);
                    if (input && errorDiv) {
                        input.classList.add('is-invalid');
                        errorDiv.textContent = error.errors[field][0];
                    }
                });

                Swal.fire({
                    icon: 'error',
                    title: 'Error de validación',
                    text: 'Por favor, corrija los errores en el formulario'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Error de conexión'
                });
            }
        });
    });

    /**
     * Abre el modal para crear o editar
     */
    window.{$functionName} = function(data = null) {
        form.reset();
        form.querySelector('[name="id"]').value = '';
        form.classList.remove('was-validated');

        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        if (data) {
            isEditMode = true;
            modalTitle.textContent = '{{ __("Editar {$name}") }}';

            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="\${key}"]`);
                if (input) {
                    if (input.type === 'checkbox') {
                        input.checked = Boolean(data[key]);
                    } else {
                        input.value = data[key] ?? '';
                    }
                }
            });
        } else {
            isEditMode = false;
            modalTitle.textContent = '{{ __("Nuevo {$name}") }}';
        }

        modalInstance.show();
    };

    // Event listener para botones de editar
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-edit')) {
            const btn = e.target.closest('.btn-edit');
            const url = btn.dataset.url;

            fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    {$functionName}(response.data);
                } else {
                    Swal.fire('Error', response.message || 'No se pudieron cargar los datos', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Error al cargar los datos', 'error');
            });
        }
    });

    // Event listener para el botón de crear
    const createButton = document.getElementById('create{$name}Button');
    if (createButton) {
        createButton.addEventListener('click', function(e) {
            e.preventDefault();
            {$functionName}();
        });
    }
});
</script>
@endpush
BLADE;
    }

    protected function generateFormFields(array $columns): string
    {
        $fields = '';

        foreach ($columns as $column) {
            $column = trim($column);
            $label = Str::title(str_replace('_', ' ', $column));

            $def = $this->findColumnDefinition($column);
            $dbType = $def['type'] ?? $this->inferMigrationTypeFromName($column);
            $nullable = $def['nullable'] ?? false;
            $required = $nullable ? '' : ' required';

            if ($dbType === 'text' || $dbType === 'json') {
                $fields .= <<<BLADE
                    <div class="mb-3">
                        <label for="{$column}" class="form-label">{{ __('{$label}') }}{$this->requiredMark($nullable)}</label>
                        <textarea class="form-control" id="{$column}" name="{$column}" rows="3" style="resize:none;"{$required}></textarea>
                        <div class="invalid-feedback" id="{$column}-error"></div>
                    </div>

BLADE;
            } elseif ($dbType === 'integer' || $dbType === 'bigInteger') {
                $fields .= <<<BLADE
                    <div class="mb-3">
                        <label for="{$column}" class="form-label">{{ __('{$label}') }}{$this->requiredMark($nullable)}</label>
                        <input type="number" class="form-control" id="{$column}" name="{$column}" step="1"{$required}>
                        <div class="invalid-feedback" id="{$column}-error"></div>
                    </div>

BLADE;
            } elseif ($dbType === 'decimal' || $dbType === 'float') {
                $fields .= <<<BLADE
                    <div class="mb-3">
                        <label for="{$column}" class="form-label">{{ __('{$label}') }}{$this->requiredMark($nullable)}</label>
                        <input type="number" class="form-control" id="{$column}" name="{$column}" step="0.01"{$required}>
                        <div class="invalid-feedback" id="{$column}-error"></div>
                    </div>

BLADE;
            } elseif ($dbType === 'boolean') {
                $fields .= <<<BLADE
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="{$column}" name="{$column}" value="1">
                        <label class="form-check-label" for="{$column}">{{ __('{$label}') }}</label>
                        <div class="invalid-feedback" id="{$column}-error"></div>
                    </div>

BLADE;
            } elseif ($dbType === 'date') {
                $fields .= <<<BLADE
                    <div class="mb-3">
                        <label for="{$column}" class="form-label">{{ __('{$label}') }}{$this->requiredMark($nullable)}</label>
                        <input type="date" class="form-control" id="{$column}" name="{$column}"{$required}>
                        <div class="invalid-feedback" id="{$column}-error"></div>
                    </div>

BLADE;
            } elseif ($dbType === 'datetime' || $dbType === 'timestamp') {
                $fields .= <<<BLADE
                    <div class="mb-3">
                        <label for="{$column}" class="form-label">{{ __('{$label}') }}{$this->requiredMark($nullable)}</label>
                        <input type="datetime-local" class="form-control" id="{$column}" name="{$column}"{$required}>
                        <div class="invalid-feedback" id="{$column}-error"></div>
                    </div>

BLADE;
            } elseif ($dbType === 'foreignId') {
                $fields .= <<<BLADE
                    <div class="mb-3">
                        <label for="{$column}" class="form-label">{{ __('{$label}') }}{$this->requiredMark($nullable)}</label>
                        <select class="form-select" id="{$column}" name="{$column}"{$required}>
                            <option value="">{{ __('Selecciona una opción') }}</option>
                            {{-- TODO: Añadir opciones --}}
                        </select>
                        <div class="invalid-feedback" id="{$column}-error"></div>
                    </div>

BLADE;
            } else {
                $maxlength = $def['length'] ?? 255;
                $fields .= <<<BLADE
                    <div class="mb-3">
                        <label for="{$column}" class="form-label">{{ __('{$label}') }}{$this->requiredMark($nullable)}</label>
                        <input type="text" class="form-control" id="{$column}" name="{$column}" maxlength="{$maxlength}"{$required}>
                        <div class="invalid-feedback" id="{$column}-error"></div>
                    </div>

BLADE;
            }
        }

        return $fields;
    }

    protected function findColumnDefinition(string $column): ?array
    {
        foreach ($this->columnDefinitions as $def) {
            if ($def['name'] === $column) {
                return $def;
            }
        }

        return null;
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

    protected function requiredMark(bool $nullable): string
    {
        return $nullable ? '' : ' <span class="text-danger">*</span>';
    }
}
