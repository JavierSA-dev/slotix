<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\DataTables\DataTableConfig;

/**
 * Componente reutilizable para tablas CRUD con Yajra DataTables.
 *
 * @see docs/components/CrudDatatable.md para documentación detallada y ejemplos de configuración.
 */
class CrudDatatable extends Component
{
    public $config;

    /**
     * Create a new component instance.
     *
     * @param array|DataTableConfig $config Configuration array or DataTableConfig object
     */
    public function __construct(array|DataTableConfig $config)
    {
        // Convertir DataTableConfig a array si es necesario
        if ($config instanceof DataTableConfig) {
            $config = $config->toArray();
        }

        $this->config = $this->validateAndNormalize($config);
    }

    private function validateAndNormalize(array $config): array
    {
        $defaults = [
            'urls' => [],
            'filters' => [],
            'actionButtons' => [], 
            'formId' => null,
            'customActions' => [],
            'table' => []
        ];
        $config = array_merge($defaults, $config);

        if (!isset($config['table']['id'])) {
            $config['table']['id'] = 'yajra-datatable';
        }
        if (!isset($config['table']['columns']) || !is_array($config['table']['columns'])) {
            $config['table']['columns'] = [];
        }

        $normalizedColumns = [];
        foreach ($config['table']['columns'] as $col) {
            if (empty($col)) continue;

            // Formato: String directo 'column_name'
            if (is_string($col)) {
                $normalizedColumns[] = [
                    'header' => ucfirst(str_replace('_', ' ', $col)),
                    'data' => $col,
                    'name' => $col,
                ];
            } 
            // Formato: Array ['Header', 'data'] (indexado)
            elseif (is_array($col) && isset($col[0]) && isset($col[1])) {
                 $normalizedColumns[] = [
                    'header' => $col[0],
                    'data' => $col[1],
                    'name' => $col[1],
                 ];
            }
            // Formato: Array asociativo ya normalizado (opcional)
            elseif (is_array($col) && isset($col['data'])) {
                $normalizedColumns[] = array_merge([
                    'header' => ucfirst($col['data']),
                    'name' => $col['data']
                ], $col);
            }
        }
        $config['table']['columns'] = $normalizedColumns;

        return $config;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.crud-datatable');
    }
}
