<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeDatatable extends Command
{
    protected $signature = 'make:datatable {name : Nombre del DataTable (ej: Product, User)}
                            {--model= : Modelo asociado (opcional, se infiere del nombre)}
                            {--columns= : Columnas separadas por coma (ej: name,email,status)}';

    protected $description = 'Crear una nueva clase DataTableConfig';

    public function handle(): int
    {
        $name = $this->argument('name');
        $name = Str::studly($name);

        // Asegurar que termina en DataTableConfig
        if (!Str::endsWith($name, 'DataTableConfig')) {
            $name .= 'DataTableConfig';
        }

        $baseName = Str::replaceLast('DataTableConfig', '', $name);
        $model = $this->option('model') ?: $baseName;
        $columns = $this->option('columns') ? explode(',', $this->option('columns')) : ['name'];

        $path = app_path("DataTables/{$name}.php");

        if (file_exists($path)) {
            $this->error("El archivo {$name}.php ya existe!");
            return self::FAILURE;
        }

        $stub = $this->generateStub($name, $baseName, $model, $columns);

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $stub);

        $this->info("DataTableConfig creado exitosamente: app/DataTables/{$name}.php");
        $this->newLine();
        $this->line("  <fg=gray>Recuerda:</>");
        $this->line("  <fg=gray>1. Añadir la ruta AJAX en routes/web.php:</>");
        $this->line("     <fg=yellow>Route::get('" . Str::kebab(Str::plural($baseName)) . "/get-ajax', [{$baseName}Controller::class, 'getAjax'])->name('" . Str::kebab(Str::plural($baseName)) . ".get-ajax');</>");
        $this->newLine();
        $this->line("  <fg=gray>2. Usar en el controlador:</>");
        $this->line("     <fg=yellow>use App\\DataTables\\{$name};</>");
        $this->line("     <fg=yellow>\$config = new {$name}();</>");
        $this->line("     <fg=yellow>return view('..." . "index', compact('config'));</>");

        return self::SUCCESS;
    }

    protected function generateStub(string $name, string $baseName, string $model, array $columns): string
    {
        $urlBase = Str::kebab(Str::plural($baseName));
        $ajaxRoute = Str::kebab(Str::plural($baseName)) . '.get-ajax';
        $createRoute = Str::kebab(Str::plural($baseName)) . '.create';

        $columnsCode = $this->generateColumnsCode($columns);

        return <<<PHP
<?php

namespace App\DataTables;

use App\DataTables\Filters\InputFilter;
use App\Models\\{$model};
use Illuminate\Support\Facades\Gate;

class {$name} extends DataTableConfig
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
{$columnsCode}
            Column::make(__('Acciones'), 'action')->orderable(false)->searchable(false)->className('text-center'),
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

        // Descomentar si usas Policies
        // if (Gate::allows('create', {$model}::class)) {
            \$buttons[] = ActionButton::make(__('Crear {$baseName}'))
                ->route('{$createRoute}')
                ->id('create{$baseName}Button')
                ->primary()
                ->icon('fas fa-plus');
        // }

        return \$buttons;
    }
}
PHP;
    }

    protected function generateColumnsCode(array $columns): string
    {
        $code = '';
        foreach ($columns as $column) {
            $column = trim($column);
            $header = Str::title(str_replace('_', ' ', $column));
            $code .= "            Column::make('{$header}', '{$column}'),\n";
        }
        return $code;
    }
}
