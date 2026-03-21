<?php

namespace App\DataTables;

use App\DataTables\Filters\InputFilter;
use App\DataTables\Filters\SelectFilter;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UserDataTableConfig extends DataTableConfig
{
    protected function ajaxRoute(): string
    {
        return 'users.getAjax';
    }

    protected function urlBase(): string
    {
        return 'users';
    }

    protected function columns(): array
    {
        return [
            Column::make('Avatar', 'avatar'),
            Column::make('Nombre', 'name'),
            Column::make('Email', 'email'),
            Column::make('Rol', 'rol')->orderable(false),
            Column::make('Empresas', 'empresas')->orderable(false)->searchable(false),
            Column::make('Activo', 'activo')->className('text-center'),
            Column::make(__('Acciones'), 'action')->orderable(false)->searchable(false),
        ];
    }

    protected function filters(): array
    {
        return [
            InputFilter::make('search')
                ->placeholder('Buscar...')
                ->style('max-width:180px;'),

            SelectFilter::make('role')
                ->fromRoute('roles.rolesAjax')
                ->placeholder('Selecciona un rol')
                ->style('max-width: 150px;'),

            SelectFilter::make('empresa')
                ->fromRoute('admin.empresas.listAjax')
                ->placeholder('Todas las empresas')
                ->style('max-width: 180px;'),
        ];
    }

    protected function actionButtons(): array
    {
        $buttons = [];

        // Solo mostrar boton de crear si tiene permiso
        if (Gate::allows('create', User::class)) {
            $buttons[] = ActionButton::make('Nuevo Usuario')
                ->url('javascript:void(0)')
                ->id('createUserButton')
                ->primary()
                ->icon('fas fa-plus');
        }

        return $buttons;
    }
}
