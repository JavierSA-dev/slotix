<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

/**
 * Servicio para manejar la lógica de negocio de usuarios.
 *
 * Beneficios:
 * - Controladores más limpios y fáciles de testear
 * - Lógica reutilizable desde múltiples lugares (web, API, comandos)
 * - Centraliza la lógica de negocio para evitar duplicación
 */
class UserService
{
    /**
     * Extensiones y MIME types permitidos para avatares.
     */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /**
     * Crear un nuevo usuario.
     */
    public function create(array $data): User
    {
        $userData = collect($data)->except([
            'newpassword', 'newpassword_confirmation',
            'role', 'permissions', 'avatar', 'empresas',
            '_token', '_method',
        ])->toArray();

        if (! empty($data['newpassword'])) {
            $userData['password'] = Hash::make($data['newpassword']);
        }

        $user = User::create($userData);

        $this->syncRolesAndPermissions($user, $data);
        $this->syncEmpresas($user, $data);

        return $user;
    }

    /**
     * Actualizar un usuario existente.
     */
    public function update(User $user, array $data): User
    {
        // Filtrar campos que no deben actualizarse directamente
        $updateData = collect($data)->except([
            'avatar', 'newpassword', 'newpassword_confirmation',
            'role', 'permissions', 'password', 'empresas',
        ])->toArray();

        $user->update($updateData);

        // Actualizar contraseña si se proporciona
        if (! empty($data['newpassword'])) {
            $this->updatePassword($user, $data['newpassword']);
        }

        // Sincronizar roles y permisos
        $this->syncRolesAndPermissions($user, $data);
        $this->syncEmpresas($user, $data);

        return $user;
    }

    /**
     * Actualizar la contraseña de un usuario.
     */
    public function updatePassword(User $user, string $newPassword): void
    {
        $user->password = Hash::make($newPassword);
        $user->save();
    }

    /**
     * Verificar si la contraseña actual es correcta.
     */
    public function verifyCurrentPassword(User $user, string $currentPassword): bool
    {
        return Hash::check($currentPassword, $user->password);
    }

    /**
     * Sincronizar roles y permisos del usuario.
     *
     * Aplica validación de jerarquía como capa adicional de seguridad.
     * Solo permite asignar roles que el usuario autenticado puede asignar.
     */
    public function syncRolesAndPermissions(User $user, array $data): void
    {
        if (array_key_exists('role', $data)) {
            $role = $data['role'] ?? null;
            $roles = $role ? [$role] : [];

            // Filtrar roles según jerarquía del usuario autenticado (defensa en profundidad)
            if (auth()->check()) {
                $assignableRoles = auth()->user()->getAssignableRoles();
                $roles = array_intersect($roles, $assignableRoles);
            }

            $user->syncRoles($roles);
        }

        if (array_key_exists('permissions', $data)) {
            $user->syncPermissions($data['permissions'] ?? []);
        }
    }

    public function syncEmpresas(User $user, array $data): void
    {
        if (array_key_exists('empresas', $data)) {
            $user->empresas()->sync($data['empresas'] ?? []);
        }
    }

    /**
     * Procesar y guardar avatar del usuario.
     *
     * @return array{success: bool, error?: string}
     */
    public function processAvatar(User $user, UploadedFile $avatar): array
    {
        // Validar MIME type real del archivo
        $mimeType = $avatar->getMimeType();
        $extension = strtolower($avatar->getClientOriginalExtension());

        if (! in_array($extension, self::ALLOWED_EXTENSIONS) || ! in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            return [
                'success' => false,
                'error' => __('messages.invalid_image_format'),
            ];
        }

        // Generar nombre seguro basado en el MIME type real
        $safeExtension = $this->getSafeExtension($mimeType);
        $imageName = 'avatar_'.uniqid().'.'.$safeExtension;
        $directory = public_path('storage/avatares');

        // Crear directorio con permisos seguros
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Eliminar avatar anterior si existe
        if ($user->avatar && file_exists(public_path('storage/avatares/'.$user->avatar))) {
            @unlink(public_path('storage/avatares/'.$user->avatar));
        }

        $avatar->move($directory, $imageName);

        $user->avatar = $imageName;
        $user->save();

        return ['success' => true];
    }

    /**
     * Eliminar un usuario.
     */
    public function delete(User $user): bool
    {
        // Eliminar avatar si existe
        if ($user->avatar && file_exists(public_path('storage/avatares/'.$user->avatar))) {
            @unlink(public_path('storage/avatares/'.$user->avatar));
        }

        return $user->delete();
    }

    /**
     * Obtener extensión segura basada en MIME type.
     */
    private function getSafeExtension(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg'
        };
    }
}
