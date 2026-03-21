<?php

namespace App\DataTables;

use App\DataTables\Filters\InputFilter;

class EmpresaDataTableConfig extends DataTableConfig
{
    protected function ajaxRoute(): string
    {
        return 'admin.empresas.getAjax';
    }

    protected function urlBase(): string
    {
        return 'admin/empresas';
    }

    protected function columns(): array
    {
        return [
            Column::make('Nombre', 'nombre'),
            Column::make('Slug', 'id'),
            Column::make('Estado', 'activo_badge')->orderable(false)->searchable(false),
            Column::make('Módulos activos', 'modulos_count')->className('text-center')->orderable(false)->searchable(false),
            Column::make('Acciones', 'action')->orderable(false)->searchable(false),
        ];
    }

    protected function filters(): array
    {
        return [
            InputFilter::make('nombre')
                ->placeholder('Buscar empresa...')
                ->style('max-width:200px;'),
        ];
    }
}
