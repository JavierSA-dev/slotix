<?php

namespace App\DataTables;

use App\DataTables\Filters\InputFilter;
use App\DataTables\Filters\SelectFilter;

class ReservaDataTableConfig extends DataTableConfig
{
    protected function ajaxRoute(): string
    {
        return 'admin.reservas.getAjax';
    }

    protected function urlBase(): string
    {
        return 'admin/reservas';
    }

    protected function columns(): array
    {
        return [
            Column::make('Fecha', 'fecha_fmt'),
            Column::make('Hora', 'hora_fmt')->orderable(false),
            Column::make('Nombre', 'nombre'),
            Column::make('Email', 'email'),
            Column::make('Personas', 'num_personas')->className('text-center'),
            Column::make('Estado', 'estado_badge')->orderable(false)->searchable(false),
            Column::make('Acciones', 'action')->orderable(false)->searchable(false),
        ];
    }

    protected function filters(): array
    {
        return [
            InputFilter::make('fecha')
                ->inputType('date')
                ->placeholder('Fecha')
                ->style('max-width:150px;'),

            SelectFilter::make('estado')
                ->placeholder('Todos los estados')
                ->options([
                    '' => 'Todos',
                    'pendiente' => 'Pendiente',
                    'confirmada' => 'Confirmada',
                    'cancelada' => 'Cancelada',
                ])
                ->style('max-width:170px;'),
        ];
    }
}
