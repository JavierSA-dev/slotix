<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generar contraseña dinámica: iniciotaller + 6 caracteres del APP_KEY
        $appKey = config('app.key');
        $randomSuffix = substr(str_replace(['base64:', '/', '+', '='], '', $appKey), 0, 6);
        $password = 'iniciotaller' . $randomSuffix;

        $user = User::firstOrCreate(
            ['email' => 'desarrollo@tallerempresarial.es'],
            [
                'name' => 'Taller',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'activo' => true,
            ]
        );

        // Mostrar la contraseña solo si el usuario fue creado (no existía)
        if ($user->wasRecentlyCreated) {
            $this->command->newLine();
            $this->command->warn('╔═══════════════════════════════════════════════════════════╗');
            $this->command->warn('║           CREDENCIALES DEL SUPERADMIN CREADO              ║');
            $this->command->warn('╚═══════════════════════════════════════════════════════════╝');
            $this->command->info("  Email:    desarrollo@tallerempresarial.es");
            $this->command->info("  Password: {$password}");
            $this->command->newLine();
            $this->command->error('  ¡GUARDA ESTA CONTRASEÑA! No se mostrará de nuevo.');
            $this->command->newLine();
        } else {
            $this->command->info('Usuario SuperAdmin ya existía, no se modificó la contraseña.');
        }
    }
}
