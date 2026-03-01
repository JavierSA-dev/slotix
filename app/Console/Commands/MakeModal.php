<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeModal extends Command
{
    protected $signature = 'make:modal {model : Nombre del modelo (ej: Product, User)}
                            {--name= : Nombre del modal (default: modal)}
                            {--title= : Título del modal}
                            {--from-datatable= : Leer campos desde un DataTableConfig existente}
                            {--from-model : Leer campos desde el $fillable del modelo}
                            {--quick : Modo rápido, usa valores por defecto sin preguntar}';

    protected $description = 'Crear un modal de formulario Bootstrap con campos configurables';

    protected array $fields = [];
    protected string $modelName = '';
    protected string $urlBase = '';

    public function handle(): int
    {
        $this->modelName = Str::studly($this->argument('model'));
        $modelKebab = Str::kebab($this->modelName);
        $modelPlural = Str::kebab(Str::plural($this->modelName));
        $this->urlBase = $modelPlural;
        $modalName = $this->option('name') ?? 'modal';
        $title = $this->option('title') ?? "Nuevo {$this->modelName}";

        $this->info("Creando modal para: {$this->modelName}");
        $this->newLine();

        // Intentar cargar campos desde DataTableConfig o Modelo
        $preloadedFields = [];

        if ($this->option('from-datatable')) {
            $preloadedFields = $this->loadFieldsFromDatatable($this->option('from-datatable'));
        } elseif ($this->option('from-model')) {
            $preloadedFields = $this->loadFieldsFromModel();
        }

        // Configurar campos
        if (!empty($preloadedFields)) {
            $this->configurePreloadedFields($preloadedFields);
        } else {
            $this->configureFieldsInteractively();
        }

        if (empty($this->fields)) {
            $this->error('Debes añadir al menos un campo');
            return self::FAILURE;
        }

        // Crear directorio si no existe
        $viewDir = resource_path("views/{$modelPlural}/partials");
        if (!is_dir($viewDir)) {
            mkdir($viewDir, 0755, true);
        }

        // Generar el modal
        $path = "{$viewDir}/{$modalName}.blade.php";
        if (file_exists($path)) {
            if (!$this->confirm("El archivo {$modalName}.blade.php ya existe. ¿Sobrescribir?", false)) {
                $this->info('Operación cancelada.');
                return self::SUCCESS;
            }
        }

        $stub = $this->generateModalStub($modelKebab, $modalName, $title);
        file_put_contents($path, $stub);

        $this->newLine();
        $this->info("  ✓ Modal creado: resources/views/{$modelPlural}/partials/{$modalName}.blade.php");

        $this->showUsageInstructions($modelPlural, $modalName, $modelKebab);

        return self::SUCCESS;
    }

    protected function loadFieldsFromDatatable(string $datatableName): array
    {
        $className = Str::studly($datatableName);
        if (!str_ends_with($className, 'DataTableConfig')) {
            $className .= 'DataTableConfig';
        }

        $filePath = app_path("DataTables/{$className}.php");

        if (!file_exists($filePath)) {
            $this->warn("  No se encontró el archivo: app/DataTables/{$className}.php");
            return [];
        }

        $this->info("  Leyendo configuración desde: {$className}");

        // Leer el archivo y extraer información
        $content = file_get_contents($filePath);

        // Extraer urlBase si existe
        if (preg_match("/protected function urlBase\(\).*?return ['\"](.+?)['\"];/s", $content, $matches)) {
            $this->urlBase = $matches[1];
        }

        // Extraer columnas del método columns()
        $fields = [];
        if (preg_match("/protected function columns\(\).*?return \[(.*?)\];/s", $content, $matches)) {
            $columnsContent = $matches[1];

            // Buscar Column::make('Header', 'field')
            preg_match_all("/Column::make\(['\"](.+?)['\"]\s*,\s*['\"](.+?)['\"]\)/", $columnsContent, $columnMatches, PREG_SET_ORDER);

            foreach ($columnMatches as $match) {
                $header = $match[1];
                $field = $match[2];

                // Ignorar columna de acciones
                if ($field === 'action' || $field === 'actions') {
                    continue;
                }

                $fields[] = [
                    'name' => $field,
                    'label' => $header,
                ];
            }
        }

        if (empty($fields)) {
            $this->warn("  No se pudieron extraer columnas del DataTableConfig");
            // Intentar desde el modelo como fallback
            return $this->loadFieldsFromModel();
        }

        $this->info("  ✓ Se encontraron " . count($fields) . " campos");
        return $fields;
    }

    protected function loadFieldsFromModel(): array
    {
        $filePath = app_path("Models/{$this->modelName}.php");

        if (!file_exists($filePath)) {
            $this->warn("  No se encontró el modelo: app/Models/{$this->modelName}.php");
            return [];
        }

        $this->info("  Leyendo campos desde: {$this->modelName} (fillable)");

        // Leer el archivo y extraer $fillable
        $content = file_get_contents($filePath);

        $fields = [];
        if (preg_match("/protected\s+\\\$fillable\s*=\s*\[(.*?)\]/s", $content, $matches)) {
            $fillableContent = $matches[1];

            // Extraer nombres de campos
            preg_match_all("/['\"](.+?)['\"]/", $fillableContent, $fieldMatches);

            foreach ($fieldMatches[1] as $field) {
                $fields[] = [
                    'name' => $field,
                    'label' => Str::title(str_replace('_', ' ', $field)),
                ];
            }
        }

        if (empty($fields)) {
            $this->warn("  No se encontró \$fillable en el modelo o está vacío");
            return [];
        }

        $this->info("  ✓ Se encontraron " . count($fields) . " campos en fillable");
        return $fields;
    }

    protected function configurePreloadedFields(array $preloadedFields): void
    {
        $types = $this->getFieldTypes();
        $quickMode = $this->option('quick');

        $this->newLine();
        $this->line("<fg=cyan>Configuración de campos del modal:</>");

        if ($quickMode) {
            $this->line("<fg=gray>  (Modo rápido activado - usando valores por defecto)</>");
        }

        $this->newLine();

        foreach ($preloadedFields as $preloaded) {
            $name = $preloaded['name'];
            $label = $preloaded['label'];

            $this->line("<fg=yellow>Campo: {$name}</>");

            // Inferir tipo basándose en el nombre del campo
            $inferredType = $this->inferFieldType($name);
            $inferredTypeIndex = array_search($inferredType, array_keys($types));

            if ($quickMode) {
                // Modo rápido: usar valores inferidos
                $typeKey = $inferredType;
                $colWidth = $this->inferColumnWidth($name, count($preloadedFields));
                $required = !str_contains($name, 'optional') && !in_array($name, ['description', 'notes', 'comments']);
            } else {
                // Modo interactivo
                $type = $this->choice(
                    "  Tipo de campo",
                    array_values($types),
                    $inferredTypeIndex ?? 0
                );
                $typeKey = array_search($type, $types);

                $defaultCol = $this->inferColumnWidth($name, count($preloadedFields));
                $colWidth = $this->choice(
                    "  Ancho de columna",
                    ['12' => '12 (Completo)', '6' => '6 (Mitad)', '4' => '4 (Tercio)', '3' => '3 (Cuarto)', '9' => '9', '8' => '8'],
                    array_search($defaultCol, ['12', '6', '4', '3', '9', '8']) ?? 0
                );
                $colWidth = explode(' ', $colWidth)[0];

                $required = $this->confirm("  ¿Campo requerido?", true);

                // Label personalizado
                $label = $this->ask("  Label", $label);
            }

            $field = [
                'name'     => $name,
                'type'     => $typeKey,
                'label'    => $label,
                'colWidth' => $colWidth,
                'required' => $required,
            ];

            // Configuraciones adicionales según el tipo (solo en modo no-quick)
            if (!$quickMode) {
                $field = $this->addTypeSpecificOptions($field, $typeKey);
            } else {
                // Valores por defecto para modo rápido
                if ($typeKey === 'decimal') {
                    $field['step'] = '0.01';
                    $field['min'] = '0';
                }
                if ($typeKey === 'text') {
                    $field['maxlength'] = '255';
                }
            }

            $this->fields[] = $field;

            if (!$quickMode) {
                $this->newLine();
            }
        }

        // Preguntar si quiere añadir más campos
        if (!$quickMode && $this->confirm('¿Añadir más campos manualmente?', false)) {
            $this->configureFieldsInteractively();
        }
    }

    protected function inferFieldType(string $fieldName): string
    {
        $name = strtolower($fieldName);

        // Patrones comunes
        if (str_ends_with($name, '_id')) return 'select';
        if (str_contains($name, 'email')) return 'email';
        if (str_contains($name, 'password')) return 'password';
        if (str_contains($name, 'price') || str_contains($name, 'amount') || str_contains($name, 'total') || str_contains($name, 'cost')) return 'decimal';
        if (str_contains($name, 'quantity') || str_contains($name, 'stock') || str_contains($name, 'count')) return 'number';
        if (str_contains($name, 'date') || $name === 'inicio' || $name === 'fin' || $name === 'fecha') return 'date';
        if (str_contains($name, 'time') || $name === 'hora') return 'time';
        if (str_contains($name, 'datetime')) return 'datetime';
        if (str_contains($name, 'description') || str_contains($name, 'content') || str_contains($name, 'body') || str_contains($name, 'text') || str_contains($name, 'notes')) return 'textarea';
        if (str_contains($name, 'active') || str_contains($name, 'enabled') || str_contains($name, 'is_') || str_contains($name, 'has_')) return 'checkbox';
        if (str_contains($name, 'image') || str_contains($name, 'avatar') || str_contains($name, 'photo') || str_contains($name, 'file') || str_contains($name, 'document')) return 'file';

        return 'text';
    }

    protected function inferColumnWidth(string $fieldName, int $totalFields): string
    {
        $name = strtolower($fieldName);

        // Campos que típicamente ocupan todo el ancho
        if (str_contains($name, 'description') || str_contains($name, 'content') || str_contains($name, 'body') || str_contains($name, 'notes')) {
            return '12';
        }

        // Campos de nombre/título suelen ocupar más
        if ($name === 'name' || $name === 'title' || $name === 'nombre' || $name === 'titulo') {
            return $totalFields <= 3 ? '12' : '6';
        }

        // Por defecto, distribuir equitativamente
        if ($totalFields <= 2) return '12';
        if ($totalFields <= 4) return '6';
        if ($totalFields <= 6) return '6';

        return '6';
    }

    protected function getFieldTypes(): array
    {
        return [
            'text'     => 'text (Texto corto)',
            'email'    => 'email (Correo electrónico)',
            'number'   => 'number (Número entero)',
            'decimal'  => 'decimal (Número con decimales)',
            'password' => 'password (Contraseña)',
            'textarea' => 'textarea (Texto largo)',
            'select'   => 'select (Desplegable)',
            'date'     => 'date (Fecha)',
            'datetime' => 'datetime-local (Fecha y hora)',
            'time'     => 'time (Hora)',
            'checkbox' => 'checkbox (Casilla)',
            'file'     => 'file (Archivo)',
            'hidden'   => 'hidden (Oculto)',
        ];
    }

    protected function addTypeSpecificOptions(array $field, string $typeKey): array
    {
        if ($typeKey === 'text') {
            $maxLength = $this->ask("  Longitud máxima (Enter para 255)", '255');
            $field['maxlength'] = $maxLength;
        }

        if ($typeKey === 'decimal' || $typeKey === 'number') {
            $field['min'] = $this->ask("  Valor mínimo (Enter para ninguno)", null);
            $field['max'] = $this->ask("  Valor máximo (Enter para ninguno)", null);
            if ($typeKey === 'decimal') {
                $field['step'] = $this->ask("  Incremento/Step (ej: 0.01)", '0.01');
            }
        }

        if ($typeKey === 'textarea') {
            $field['rows'] = $this->ask("  Número de filas", '3');
        }

        if ($typeKey === 'select') {
            $field['placeholder'] = $this->ask("  Texto opción vacía", 'Selecciona una opción');
        }

        if ($typeKey === 'file') {
            $field['accept'] = $this->ask("  Tipos aceptados (ej: image/*, .pdf)", '');
        }

        // Texto de ayuda opcional
        $helpText = $this->ask("  Texto de ayuda (Enter para ninguno)", null);
        if ($helpText) {
            $field['helpText'] = $helpText;
        }

        return $field;
    }

    protected function configureFieldsInteractively(): void
    {
        $types = $this->getFieldTypes();

        if (empty($this->fields)) {
            $this->line("<fg=cyan>Configuración de campos del modal:</>");
            $this->line("<fg=gray>  (Escribe 'fin' en el nombre del campo para terminar)</>");
            $this->newLine();
        }

        $fieldIndex = count($this->fields) + 1;
        while (true) {
            $this->line("<fg=yellow>Campo #{$fieldIndex}</>");

            $name = $this->ask("  Nombre del campo (o 'fin' para terminar)");

            if (strtolower($name) === 'fin' || empty($name)) {
                break;
            }

            $name = Str::snake($name);

            $inferredType = $this->inferFieldType($name);
            $inferredTypeIndex = array_search($inferredType, array_keys($types));

            $type = $this->choice(
                "  Tipo de campo",
                array_values($types),
                $inferredTypeIndex ?? 0
            );
            $typeKey = array_search($type, $types);

            $defaultLabel = Str::title(str_replace('_', ' ', $name));
            $label = $this->ask("  Label (Enter para '{$defaultLabel}')", $defaultLabel);

            $colWidth = $this->choice(
                "  Ancho de columna (Bootstrap)",
                ['12' => '12 (Ancho completo)', '6' => '6 (Mitad)', '4' => '4 (Tercio)', '3' => '3 (Cuarto)', '9' => '9', '8' => '8'],
                0
            );
            $colWidth = explode(' ', $colWidth)[0];

            $required = $this->confirm("  ¿Campo requerido?", true);

            $field = [
                'name'     => $name,
                'type'     => $typeKey,
                'label'    => $label,
                'colWidth' => $colWidth,
                'required' => $required,
            ];

            $field = $this->addTypeSpecificOptions($field, $typeKey);

            $this->fields[] = $field;
            $fieldIndex++;
            $this->newLine();
        }
    }

    protected function generateModalStub(string $modelKebab, string $modalName, string $title): string
    {
        $modalId = "{$modelKebab}-{$modalName}";
        $formId = "{$modelKebab}Form";
        $studlyModel = Str::studly($modelKebab);

        $fieldsHtml = $this->generateFieldsHtml();

        return <<<BLADE
{{-- Modal {$title} --}}
<div class="modal fade" id="{$modalId}" tabindex="-1" aria-labelledby="{$modalId}-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{$modalId}-label">{$title}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="{$formId}">
                <div class="modal-body">
                    {{-- Campo oculto para el ID (edición) --}}
                    <input type="hidden" id="{$modelKebab}_id" name="id">

                    <div class="row">
{$fieldsHtml}
                    </div>
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

@push('scripts')
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
        isEditMode = false;
    });

    // Envío del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const id = form.querySelector('[name="id"]').value;
        isEditMode = id !== '';

        const url = isEditMode
            ? `{{ url('{$this->urlBase}') }}/\${id}`
            : '{{ route("{$this->urlBase}.store") }}';

        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        // Manejar checkboxes no marcados
        form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            if (!cb.checked) data[cb.name] = 0;
        });

        fetch(url, {
            method: isEditMode ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
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
                // Recargar DataTable si existe
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
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error de conexión'
            });
        });
    });

    /**
     * Abre el modal para crear o editar
     * @param {Object|null} data - Datos para edición, null para creación
     */
    window.open{$studlyModel}Modal = function(data = null) {
        form.reset();
        form.querySelector('[name="id"]').value = '';

        if (data) {
            // Modo edición
            isEditMode = true;
            modalTitle.textContent = 'Editar {$this->modelName}';

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
            // Modo creación
            isEditMode = false;
            modalTitle.textContent = '{$title}';
        }

        modalInstance.show();
    };

    // Event listener para botones de editar en la DataTable
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
                    open{$studlyModel}Modal(response.data);
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
});
</script>
@endpush
BLADE;
    }

    protected function generateFieldsHtml(): string
    {
        $html = '';

        foreach ($this->fields as $field) {
            $html .= $this->generateFieldHtml($field);
        }

        return $html;
    }

    protected function generateFieldHtml(array $field): string
    {
        $name = $field['name'];
        $type = $field['type'];
        $label = $field['label'];
        $colWidth = $field['colWidth'];
        $required = $field['required'];
        $requiredMark = $required ? ' <span class="text-danger">*</span>' : '';
        $requiredAttr = $required ? ' required' : '';

        $indent = '                        ';

        $html = "{$indent}<div class=\"col-md-{$colWidth} mb-3\">\n";

        // Label (excepto para hidden y checkbox)
        if ($type !== 'hidden' && $type !== 'checkbox') {
            $html .= "{$indent}    <label for=\"{$name}\" class=\"form-label\">{$label}{$requiredMark}</label>\n";
        }

        // Generar el input según el tipo
        switch ($type) {
            case 'textarea':
                $rows = $field['rows'] ?? 3;
                $html .= "{$indent}    <textarea class=\"form-control\" id=\"{$name}\" name=\"{$name}\" rows=\"{$rows}\"{$requiredAttr}></textarea>\n";
                break;

            case 'select':
                $placeholder = $field['placeholder'] ?? 'Selecciona una opción';
                $html .= "{$indent}    <select class=\"form-select\" id=\"{$name}\" name=\"{$name}\"{$requiredAttr}>\n";
                $html .= "{$indent}        <option value=\"\">{$placeholder}</option>\n";
                $html .= "{$indent}        {{-- TODO: Añadir opciones dinámicamente --}}\n";
                $html .= "{$indent}    </select>\n";
                break;

            case 'checkbox':
                $html .= "{$indent}    <div class=\"form-check\">\n";
                $html .= "{$indent}        <input type=\"checkbox\" class=\"form-check-input\" id=\"{$name}\" name=\"{$name}\" value=\"1\">\n";
                $html .= "{$indent}        <label class=\"form-check-label\" for=\"{$name}\">{$label}{$requiredMark}</label>\n";
                $html .= "{$indent}    </div>\n";
                break;

            case 'hidden':
                $html .= "{$indent}    <input type=\"hidden\" id=\"{$name}\" name=\"{$name}\">\n";
                break;

            case 'file':
                $accept = isset($field['accept']) && $field['accept'] ? " accept=\"{$field['accept']}\"" : '';
                $html .= "{$indent}    <input type=\"file\" class=\"form-control\" id=\"{$name}\" name=\"{$name}\"{$accept}{$requiredAttr}>\n";
                break;

            case 'decimal':
                $min = isset($field['min']) && $field['min'] !== null ? " min=\"{$field['min']}\"" : '';
                $max = isset($field['max']) && $field['max'] !== null ? " max=\"{$field['max']}\"" : '';
                $step = isset($field['step']) ? " step=\"{$field['step']}\"" : ' step="0.01"';
                $html .= "{$indent}    <input type=\"number\" class=\"form-control\" id=\"{$name}\" name=\"{$name}\"{$step}{$min}{$max}{$requiredAttr}>\n";
                break;

            case 'number':
                $min = isset($field['min']) && $field['min'] !== null ? " min=\"{$field['min']}\"" : '';
                $max = isset($field['max']) && $field['max'] !== null ? " max=\"{$field['max']}\"" : '';
                $html .= "{$indent}    <input type=\"number\" class=\"form-control\" id=\"{$name}\" name=\"{$name}\"{$min}{$max}{$requiredAttr}>\n";
                break;

            case 'text':
                $maxlength = isset($field['maxlength']) ? " maxlength=\"{$field['maxlength']}\"" : '';
                $html .= "{$indent}    <input type=\"text\" class=\"form-control\" id=\"{$name}\" name=\"{$name}\"{$maxlength}{$requiredAttr}>\n";
                break;

            default:
                $html .= "{$indent}    <input type=\"{$type}\" class=\"form-control\" id=\"{$name}\" name=\"{$name}\"{$requiredAttr}>\n";
        }

        // Texto de ayuda
        if (isset($field['helpText'])) {
            $html .= "{$indent}    <small class=\"text-muted\">{$field['helpText']}</small>\n";
        }

        $html .= "{$indent}</div>\n";

        return $html;
    }

    protected function showUsageInstructions(string $modelPlural, string $modalName, string $modelKebab): void
    {
        $studlyModel = Str::studly($modelKebab);

        $this->newLine();
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("<fg=green>Incluye el modal en tu vista index:</>");
        $this->line("<fg=yellow>@include('{$modelPlural}.partials.{$modalName}')</>");
        $this->newLine();
        $this->line("<fg=green>El modal ya escucha automáticamente los clicks en .btn-edit</>");
        $this->newLine();
        $this->line("<fg=green>Para el botón 'Crear', usa:</>");
        $this->line("<fg=yellow>onclick=\"open{$studlyModel}Modal()\"</>");
        $this->newLine();
        $this->line("<fg=green>O modifica tu DataTableConfig actionButtons():</>");
        $this->line("<fg=yellow>ActionButton::make('Crear')</>");
        $this->line("<fg=yellow>    ->id('btnCreate{$studlyModel}')</>");
        $this->line("<fg=yellow>    ->onClick('open{$studlyModel}Modal()')</>");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    }
}
