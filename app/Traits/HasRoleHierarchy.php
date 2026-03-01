<?php

namespace App\Traits;

use Spatie\Permission\Models\Role;

/**
 * Trait para gestionar jerarquía de roles.
 *
 * Permite verificar si un usuario puede asignar ciertos roles
 * basándose en su nivel jerárquico.
 */
trait HasRoleHierarchy
{
    /**
     * Obtener el nivel jerárquico del usuario actual.
     * Retorna el nivel más alto (número más bajo) de sus roles.
     *
     * @return int Nivel del usuario (1 = máximo privilegio, PHP_INT_MAX = sin rol)
     */
    public function getHierarchyLevel(): int
    {
        $hierarchy = config('roles.hierarchy', []);
        $userRoles = $this->getRoleNames()->toArray();

        if (empty($userRoles)) {
            return PHP_INT_MAX;
        }

        $levels = array_map(
            fn($role) => $hierarchy[$role] ?? PHP_INT_MAX,
            $userRoles
        );

        return min($levels);
    }

    /**
     * Verificar si el usuario puede asignar un rol específico.
     *
     * @param string $roleName Nombre del rol a asignar
     * @return bool
     */
    public function canAssignRole(string $roleName): bool
    {
        $hierarchy = config('roles.hierarchy', []);
        $roleLevel = $hierarchy[$roleName] ?? PHP_INT_MAX;

        return $this->getHierarchyLevel() <= $roleLevel;
    }

    /**
     * Obtener los nombres de roles que este usuario puede asignar.
     *
     * @return array Lista de nombres de roles asignables
     */
    public function getAssignableRoles(): array
    {
        $hierarchy = config('roles.hierarchy', []);
        $userLevel = $this->getHierarchyLevel();

        return array_keys(
            array_filter($hierarchy, fn($level) => $level >= $userLevel)
        );
    }

    /**
     * Obtener los objetos Role que este usuario puede asignar.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAssignableRoleModels()
    {
        $assignableNames = $this->getAssignableRoles();
        return Role::whereIn('name', $assignableNames)->get();
    }

    /**
     * Verificar si el usuario puede gestionar (editar/eliminar) a otro usuario.
     *
     * @param \App\Models\User $targetUser Usuario objetivo
     * @return bool
     */
    public function canManageUser($targetUser): bool
    {
        // Siempre puede gestionar su propio perfil
        if ($this->id === $targetUser->id) { return true; }

        $myLevel = $this->getHierarchyLevel();
        $targetLevel = $targetUser->getHierarchyLevel();
        
        // A partir de nivel 3 (User o similares) no pueden gestionar a nadie que no sean ellos mismos.
        if ($myLevel >= 3) { return false; }
        
        return $myLevel <= $targetLevel;
    }

    /**
     * Verificar si el usuario puede crear nuevos usuarios.
     *
     * @return bool
     */
    public function canCreateUsers(): bool
    {
        $allowedRoles = config('roles.can_manage_users', []);

        foreach ($this->getRoleNames() as $roleName) {
            if (in_array($roleName, $allowedRoles)) {
                return true;
            }
        }

        return false;
    }
}
