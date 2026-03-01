<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Helper para generar botones de acción en DataTables.
 *
 * Centraliza el HTML de los botones para evitar duplicación en controladores.
 *
 * Uso básico:
 *   DataTableActions::render($row, 'users')
 *
 * Con permisos (Policy):
 *   DataTableActions::render($row, 'users', withPolicy: true)
 *
 * Personalizado:
 *   DataTableActions::render($row, 'users', show: false, editAsButton: true)
 */
class DataTableActions
{
    /**
     * Renderizar botones de acción para una fila de DataTable.
     *
     * @param Model $row          El modelo/registro actual
     * @param string $routePrefix Prefijo de rutas (ej: 'users', 'roles')
     * @param bool $show          Mostrar botón ver
     * @param bool $edit          Mostrar botón editar
     * @param bool $delete        Mostrar botón eliminar
     * @param bool $withPolicy    Verificar permisos con Policy/Gates
     * @param bool $editAsButton  Usar button en lugar de anchor para editar (para modales AJAX)
     * @param bool $deleteAsButton Usar button con AJAX en lugar de form para eliminar
     */
    public static function render(
        Model $row,
        string $routePrefix,
        bool $show = true,
        bool $edit = true,
        bool $delete = true,
        bool $withPolicy = false,
        bool $editAsButton = false,
        bool $deleteAsButton = false
    ): string {
        $btn = '<div class="d-flex gap-1 justify-content-center">';

        // Botón Ver
        if ($show && self::canPerform($row, 'view', $withPolicy)) {
            $btn .= self::viewButton($row, $routePrefix);
        }

        // Botón Editar
        if ($edit && self::canPerform($row, 'update', $withPolicy)) {
            $btn .= $editAsButton
                ? self::editButtonAjax($row, $routePrefix)
                : self::editButtonLink($row, $routePrefix);
        }

        // Botón Eliminar
        if ($delete && self::canPerform($row, 'delete', $withPolicy)) {
            $btn .= $deleteAsButton
                ? self::deleteButtonAjax($row, $routePrefix)
                : self::deleteButtonForm($row, $routePrefix);
        }

        $btn .= '</div>';

        return $btn;
    }

    /**
     * Verificar si el usuario puede realizar la acción.
     */
    private static function canPerform(Model $row, string $ability, bool $withPolicy): bool
    {
        if (!$withPolicy) {
            return true;
        }

        return Gate::allows($ability, $row);
    }

    /**
     * Botón Ver (link).
     */
    private static function viewButton(Model $row, string $routePrefix): string
    {
        return '<a href="' . route("{$routePrefix}.show", $row->id) . '" '
            . 'class="btn btn-sm btn-primary" '
            . 'title="' . __('botones.Ver') . '">'
            . '<i class="fa fa-eye"></i></a>';
    }

    /**
     * Botón Editar como link (para navegación a página de edición).
     */
    private static function editButtonLink(Model $row, string $routePrefix): string
    {
        return '<a href="' . route("{$routePrefix}.edit", $row->id) . '" '
            . 'class="btn btn-sm btn-success" '
            . 'title="' . __('botones.Editar') . '">'
            . '<i class="fa fa-edit"></i></a>';
    }

    /**
     * Botón Editar como button (para abrir modal AJAX).
     */
    private static function editButtonAjax(Model $row, string $routePrefix): string
    {
        return '<button type="button" '
            . 'class="btn btn-sm btn-success btn-edit" '
            . 'data-url="' . route("{$routePrefix}.edit", $row->id) . '" '
            . 'title="' . __('botones.Editar') . '">'
            . '<i class="fa fa-edit"></i></button>';
    }

    /**
     * Botón Eliminar con formulario (submit tradicional).
     */
    private static function deleteButtonForm(Model $row, string $routePrefix): string
    {
        return '<form action="' . route("{$routePrefix}.destroy", $row->id) . '" method="POST" class="d-inline">'
            . csrf_field() . method_field('DELETE')
            . '<button type="submit" class="btn btn-danger btn-sm" '
            . 'title="' . __('botones.Eliminar') . '" '
            . 'onclick="return confirm(\'' . __('messages.estas_seguro') . '\')">'
            . '<i class="fa fa-trash"></i></button></form>';
    }

    /**
     * Botón Eliminar como button (para AJAX con SweetAlert).
     */
    private static function deleteButtonAjax(Model $row, string $routePrefix): string
    {
        return '<button type="button" '
            . 'class="btn btn-danger btn-sm btn-delete" '
            . 'data-url="' . route("{$routePrefix}.destroy", $row->id) . '" '
            . 'title="' . __('botones.Eliminar') . '">'
            . '<i class="fa fa-trash"></i></button>';
    }
}
