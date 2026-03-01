<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Ejecutar antes de cualquier otro check.
     * Si devuelve true/false, se usa ese resultado.
     * Si devuelve null, continua con el metodo especifico.
     */
    public function before(User $user, string $ability): ?bool
    {
        // SuperAdmin tiene acceso a todo
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        return null;
    }

    /**
     * Determina si el usuario puede ver el listado.
     * Metodo: index()
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.index');
    }

    /**
     * Determina si el usuario puede ver un registro especifico.
     * Metodo: show()
     */
    public function view(User $user, User $model): bool
    {
        // Cualquier usuario puede ver su propio perfil
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermissionTo('users.show');
    }

    /**
     * Determina si el usuario puede crear registros.
     * Metodos: create(), store()
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.create');
    }

    /**
     * Determina si el usuario puede actualizar un registro.
     * Metodos: edit(), update()
     *
     * Usa jerarquía: solo puede editar usuarios de nivel inferior.
     */
    public function update(User $user, User $model): bool
    {
        // Cualquier usuario puede editar su propio perfil
        if ($user->id === $model->id) {
            return true;
        }

        // Verificar permiso base
        if (!$user->hasPermissionTo('users.edit')) {
            return false;
        }

        // Verificar jerarquía: solo puede editar usuarios de nivel inferior
        return $user->canManageUser($model);
    }

    /**
     * Determina si el usuario puede eliminar un registro.
     * Metodo: destroy()
     *
     * Usa jerarquía: solo puede eliminar usuarios de nivel inferior.
     */
    public function delete(User $user, User $model): bool
    {
        // Nadie puede eliminarse a sí mismo
        if ($user->id === $model->id) {
            return false;
        }

        // Verificar permiso base
        if (!$user->hasPermissionTo('users.delete')) {
            return false;
        }

        // Verificar jerarquía: solo puede eliminar usuarios de nivel estrictamente inferior
        // canManageUser ya excluye usuarios del mismo nivel o superior
        return $user->canManageUser($model);
    }
}
