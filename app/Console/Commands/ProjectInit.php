<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ProjectInit extends Command
{
    protected $signature = 'project:init
                            {--name= : Nombre de la aplicación}
                            {--db= : Nombre de la base de datos}
                            {--skip-npm : Saltar npm install}
                            {--skip-build : Saltar npm run build}
                            {--skip-migrate : Saltar migraciones}
                            {--force : Sobrescribir .env si existe}';

    protected $description = 'Inicializa el proyecto TE2.0 configurando entorno, dependencias y base de datos';

    private array $steps = [];
    private int $currentStep = 0;
    private bool $userExistedBefore = false;

    public function handle(): int
    {
        $this->showBanner();

        // Verificar requisitos
        if (!$this->checkRequirements()) {
            return Command::FAILURE;
        }

        // Recopilar información
        $appName = $this->option('name') ?? $this->ask('Nombre de la aplicación', 'TE2.0');
        $dbName = $this->option('db') ?? $this->ask('Nombre de la base de datos');

        if (empty($dbName)) {
            $this->error('El nombre de la base de datos es obligatorio.');
            return Command::FAILURE;
        }

        // Confirmar configuración
        $this->info('');
        $this->table(
            ['Configuración', 'Valor'],
            [
                ['Aplicación', $appName],
                ['Base de datos', $dbName],
            ]
        );

        if (!$this->option('no-interaction') && !$this->confirm('¿Es correcto?', true)) {
            $this->warn('Inicialización cancelada.');
            return Command::INVALID;
        }

        // Ejecutar pasos
        $this->steps = $this->buildSteps($appName, $dbName);

        $this->info('');
        $this->info('Iniciando configuración del proyecto...');
        $this->info('');

        foreach ($this->steps as $index => $step) {
            $this->currentStep = $index + 1;
            $totalSteps = count($this->steps);

            $this->line("<fg=cyan>[{$this->currentStep}/{$totalSteps}]</> {$step['description']}...");

            try {
                $result = call_user_func($step['action']);
                if ($result === false) {
                    $this->error("   ✗ Error en: {$step['description']}");
                    return Command::FAILURE;
                }
                $this->info("   <fg=green>✓</> Completado");
            } catch (\Exception $e) {
                $this->error("   ✗ Error: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $this->showSuccess($appName, $dbName);

        return Command::SUCCESS;
    }

    private function showBanner(): void
    {
        $this->info('');
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║           TALLER EMPRESARIAL 2.0 - STARTER KIT            ║');
        $this->info('║                  Inicializador de Proyecto                ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->info('');
    }

    private function checkRequirements(): bool
    {
        $this->info('Verificando requisitos...');

        // Verificar Node/NPM
        $npmVersion = shell_exec('npm --version 2>&1');
        if (empty($npmVersion) || str_contains($npmVersion, 'not found')) {
            $this->error('✗ NPM no está instalado. Instala Node.js primero.');
            return false;
        }
        $this->line("   <fg=green>✓</> NPM v" . trim($npmVersion));

        // Verificar Composer
        $composerVersion = shell_exec('composer --version 2>&1');
        if (empty($composerVersion) || str_contains($composerVersion, 'not found')) {
            $this->error('✗ Composer no está instalado.');
            return false;
        }
        $this->line("   <fg=green>✓</> " . explode("\n", $composerVersion)[0]);

        // Verificar que existe .env.example
        if (!File::exists(base_path('.env.example'))) {
            $this->error('✗ No se encuentra .env.example');
            return false;
        }
        $this->line("   <fg=green>✓</> .env.example encontrado");

        $this->info('');
        return true;
    }

    private function buildSteps(string $appName, string $dbName): array
    {
        $steps = [];

        // Paso 1: Crear .env
        $steps[] = [
            'description' => 'Configurando archivo .env',
            'action' => fn() => $this->setupEnvFile($appName, $dbName),
        ];

        // Paso 2: Composer install (si no hay vendor)
        if (!File::isDirectory(base_path('vendor'))) {
            $steps[] = [
                'description' => 'Instalando dependencias de Composer',
                'action' => fn() => $this->runProcess('composer install --no-interaction'),
            ];
        }

        // Paso 3: NPM install
        if (!$this->option('skip-npm')) {
            $steps[] = [
                'description' => 'Instalando dependencias de NPM',
                'action' => fn() => $this->runProcess('npm install'),
            ];
        }

        // Paso 4: Generar key
        $steps[] = [
            'description' => 'Generando APP_KEY',
            'action' => fn() => $this->callSilent('key:generate', ['--force' => true]) === 0,
        ];

        // Paso 5: Build assets
        if (!$this->option('skip-build')) {
            $steps[] = [
                'description' => 'Compilando assets (npm run build)',
                'action' => fn() => $this->runProcess('npm run build'),
            ];
        }

        // Paso 6: Crear base de datos si no existe
        if (!$this->option('skip-migrate')) {
            $steps[] = [
                'description' => 'Creando base de datos si no existe',
                'action' => fn() => $this->createDatabase($dbName),
            ];
        }

        // Paso 7: Migraciones
        if (!$this->option('skip-migrate')) {
            $steps[] = [
                'description' => 'Ejecutando migraciones y seeders',
                'action' => function () {
                    // Verificar si el usuario ya existe ANTES de migrar
                    try {
                        $this->userExistedBefore = \App\Models\User::where('email', 'desarrollo@tallerempresarial.es')->exists();
                    } catch (\Exception $e) {
                        // La tabla puede no existir aún
                        $this->userExistedBefore = false;
                    }

                    return $this->callSilent('migrate', ['--seed' => true, '--force' => true]) === 0;
                },
            ];
        }

        // Paso 7: Limpiar caché
        $steps[] = [
            'description' => 'Limpiando caché',
            'action' => fn() => $this->callSilent('optimize:clear') === 0,
        ];

        // Paso 8: Storage link
        $steps[] = [
            'description' => 'Creando enlace simbólico de storage',
            'action' => function () {
                if (!File::exists(public_path('storage'))) {
                    $this->callSilent('storage:link');
                }
                return true;
            },
        ];

        // Paso 9: Instalar git hooks
        $steps[] = [
            'description' => 'Instalando git hooks (auto-release)',
            'action' => fn() => $this->installGitHooks(),
        ];

        // Paso 10: Actualizar Laravel Boost
        $steps[] = [
            'description' => 'Actualizando Laravel Boost (guidelines y reglas)',
            'action' => fn() => $this->callSilent('boost:update') === 0,
        ];

        return $steps;
    }

    private function installGitHooks(): bool
    {
        $sourceDir = base_path('scripts/hooks');
        $targetDir = base_path('.git/hooks');

        if (!File::isDirectory($sourceDir) || !File::isDirectory($targetDir)) {
            return true; // Ignorar si no existen los directorios
        }

        foreach (File::files($sourceDir) as $hook) {
            $target = $targetDir . '/' . $hook->getFilename();
            File::copy($hook->getPathname(), $target);
            @chmod($target, 0755);
        }

        return true;
    }

    private function setupEnvFile(string $appName, string $dbName): bool
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        // Si existe .env y no hay --force, preguntar
        if (File::exists($envPath) && !$this->option('force')) {
            if (!$this->confirm('.env ya existe. ¿Sobrescribir?', false)) {
                $this->warn('   Manteniendo .env existente');
                return true;
            }
        }

        // Copiar .env.example a .env
        File::copy($envExamplePath, $envPath);

        // Leer contenido
        $content = File::get($envPath);

        // Reemplazar valores
        $replacements = [
            '/^APP_NAME=.*/m' => "APP_NAME=\"{$appName}\"",
            '/^DB_DATABASE=.*/m' => "DB_DATABASE={$dbName}",
            '/^APP_URL=.*/m' => "APP_URL=http://localhost:8000",
        ];

        foreach ($replacements as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        File::put($envPath, $content);

        return true;
    }

    private function runProcess(string $command): bool
    {
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            base_path()
        );

        if (!is_resource($process)) {
            return false;
        }

        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $this->line("   <fg=yellow>Output:</> " . substr($errors ?: $output, 0, 200));
            return false;
        }

        return true;
    }

    private function createDatabase(string $dbName): bool
    {
        try {
            $host = config('database.connections.mysql.host', '127.0.0.1');
            $port = config('database.connections.mysql.port', '3306');
            $username = config('database.connections.mysql.username', 'root');
            $password = config('database.connections.mysql.password', '');

            $pdo = new \PDO(
                "mysql:host={$host};port={$port}",
                $username,
                $password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            return true;
        } catch (\PDOException $e) {
            $this->error("   Error al crear la base de datos: " . $e->getMessage());
            return false;
        }
    }

    private function showSuccess(string $appName, string $dbName): void
    {
        $this->info('');
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║              ✓ PROYECTO INICIALIZADO CON ÉXITO            ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->info('');
        $this->table(
            ['', ''],
            [
                ['Aplicación', $appName],
                ['Base de datos', $dbName],
                ['URL', 'http://localhost:8000'],
            ]
        );
        $this->info('');

        // Mostrar credenciales con la contraseña real
        $this->showCredentials();

        $this->info('Para iniciar el servidor:');
        $this->line('   <fg=cyan>php artisan serve</>');
        $this->info('');
    }

    private function showCredentials(): void
    {
        $user = \App\Models\User::where('email', 'desarrollo@tallerempresarial.es')->first();

        $this->info('Credenciales de acceso:');
        $this->line('   Email:    <fg=cyan>desarrollo@tallerempresarial.es</>');

        if ($user && !$this->userExistedBefore) {
            // Usuario recién creado - mostrar la contraseña generada
            $password = $this->generatePassword();
            $this->line("   Password: <fg=cyan>{$password}</>");
            $this->info('');
            $this->error('   ¡GUARDA ESTA CONTRASEÑA! No se mostrará de nuevo.');
        } elseif ($user) {
            // Usuario ya existía
            $this->line('   Password: <fg=yellow>(usuario ya existía, contraseña no modificada)</>');
        } else {
            // No se pudo crear el usuario (error en migraciones?)
            $this->line('   Password: <fg=red>(error: usuario no encontrado)</>');
        }
        $this->info('');
    }

    private function generatePassword(): string
    {
        $appKey = config('app.key');
        $randomSuffix = substr(str_replace(['base64:', '/', '+', '='], '', $appKey), 0, 6);
        return 'iniciotaller' . $randomSuffix;
    }
}
