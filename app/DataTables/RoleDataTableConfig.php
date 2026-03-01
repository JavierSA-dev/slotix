<?php

namespace App\DataTables;

use App\DataTables\Filters\InputFilter;

class RoleDataTableConfig extends DataTableConfig
{
    protected function ajaxRoute(): string
    {
        return 'roles.getAjax';
    }

    protected function urlBase(): string
    {
        return 'roles';
    }

    protected function columns(): array
    {
        return [
            Column::make('Nombre', 'name'),
            Column::make('Permisos', 'permissions')->orderable(false)->searchable(false),
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
            ActionButton::make(__('titulos.Crear_Rol'))
                ->url('javascript:void(0)')
                ->id('createRoleButton')
                ->primary()
                ->icon('fas fa-plus'),
        ];
    }
}
