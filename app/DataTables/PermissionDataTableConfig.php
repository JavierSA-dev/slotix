<?php

namespace App\DataTables;

use App\DataTables\Filters\InputFilter;

class PermissionDataTableConfig extends DataTableConfig
{
    protected function ajaxRoute(): string
    {
        return 'permissions.getAjax';
    }

    protected function urlBase(): string
    {
        return 'permissions';
    }

    protected function columns(): array
    {
        return [
            Column::make('Nombre', 'name'),
            Column::make('Roles', 'roles')->orderable(false)->searchable(false),
            Column::make(__('Acciones'), 'action')->orderable(false)->searchable(false),
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
            ActionButton::make(__('titulos.Crear_Permiso'))
                ->url('javascript:void(0)')
                ->id('createPermissionButton')
                ->primary()
                ->icon('fas fa-plus'),
        ];
    }
}
