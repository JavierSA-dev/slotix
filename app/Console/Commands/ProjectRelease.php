<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ProjectRelease extends Command
{
    protected $signature = 'project:release
                            {--semver=  : Versión a publicar (ej: 1.2.0)}
                            {--patch    : Incrementar versión patch (1.0.X)}
                            {--minor    : Incrementar versión minor (1.X.0)}
                            {--major    : Incrementar versión major (X.0.0)}
                            {--dry-run  : Previsualizar sin hacer cambios}
                            {--amend    : Enmendar el último commit (usado por el git hook)}';

    protected $description = 'Publica una nueva versión: genera changelog automático y crea tag git';

    private const CATEGORIES = [
        'feat'     => 'Nuevas funcionalidades',
        'fix'      => 'Correcciones de errores',
        'perf'     => 'Mejoras de rendimiento',
        'refactor' => 'Refactorizaciones',
        'docs'     => 'Documentación',
        'test'     => 'Tests',
        'chore'    => 'Mantenimiento',
        'style'    => 'Estilos',
        'ci'       => 'CI/CD',
    ];

    public function handle(): int
    {
        $this->showBanner();

        $versionFile    = base_path('VERSION');
        $currentVersion = File::exists($versionFile)
            ? trim(File::get($versionFile))
            : '1.0.0';

        $this->info("Versión actual: <fg=cyan>{$currentVersion}</>");

        $newVersion = $this->resolveNewVersion($currentVersion);
        if (!$newVersion) {
            return Command::FAILURE;
        }

        $commits = $this->getCommitsSinceLastTag();
        $grouped = $this->groupCommits($commits);

        $this->showChangelog($newVersion, $grouped);

        if ($this->option('dry-run')) {
            $this->warn('Modo dry-run: no se han realizado cambios.');
            return Command::SUCCESS;
        }

        if (!$this->option('no-interaction')) {
            if ($this->confirm('¿Ejecutar los tests antes de publicar?', true)) {
                $this->info('');
                $passed = $this->runTestsSuite();
                $this->info('');

                if (!$passed) {
                    if (!$this->confirm('<fg=yellow>Algunos tests han fallado.</> ¿Continuar con la publicación de todos modos?', false)) {
                        $this->warn('Publicación cancelada.');
                        return Command::INVALID;
                    }
                } else {
                    $this->line('   <fg=green>✓</> Todos los tests pasaron correctamente');
                }
            }
        }

        if (!$this->option('no-interaction') && !$this->confirm("¿Publicar versión <fg=cyan>v{$newVersion}</>?", true)) {
            $this->warn('Publicación cancelada.');
            return Command::INVALID;
        }

        $date           = now()->format('d/m/Y');
        $changelogEntry = $this->buildChangelogEntry($newVersion, $date, $grouped);

        $this->info('');
        $this->info('Aplicando cambios...');
        $this->updateVersionFile($versionFile, $newVersion);
        $this->updateRootChangelog($changelogEntry);
        $this->updateDocsChangelog($newVersion, $date, $grouped);

        $this->callSilent('config:clear');
        $this->line("   <fg=green>✓</> Caché de configuración limpiada");

        $this->gitCommitAndTag($newVersion);
        $this->showSuccess($newVersion);

        return Command::SUCCESS;
    }

    // ──────────────────────────────────────────────────────────────
    //  Resolución de versión
    // ──────────────────────────────────────────────────────────────

    private function resolveNewVersion(string $current): ?string
    {
        if ($version = $this->option('semver')) {
            if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
                $this->error('Formato de versión inválido. Usa semver: X.Y.Z');
                return null;
            }
            return $version;
        }

        [$major, $minor, $patch] = array_map('intval', explode('.', $current));

        if ($this->option('major')) return ($major + 1) . '.0.0';
        if ($this->option('minor')) return $major . '.' . ($minor + 1) . '.0';
        if ($this->option('patch')) return $major . '.' . $minor . '.' . ($patch + 1);

        // Modo interactivo
        $this->info('');
        $choice = $this->choice('Tipo de incremento de versión', [
            "patch  → {$major}.{$minor}." . ($patch + 1) . '  (correcciones menores)',
            "minor  → {$major}." . ($minor + 1) . '.0  (nuevas funcionalidades)',
            "major  → " . ($major + 1) . '.0.0  (cambios incompatibles / breaking changes)',
            'custom → Especificar manualmente',
        ], 0);

        if (str_starts_with($choice, 'patch'))  return "{$major}.{$minor}." . ($patch + 1);
        if (str_starts_with($choice, 'minor'))  return "{$major}." . ($minor + 1) . '.0';
        if (str_starts_with($choice, 'major'))  return ($major + 1) . '.0.0';

        $custom = $this->ask('Nueva versión (X.Y.Z)', "{$major}.{$minor}." . ($patch + 1));
        if (!preg_match('/^\d+\.\d+\.\d+$/', $custom)) {
            $this->error('Formato inválido. Usa semver: X.Y.Z');
            return null;
        }

        return $custom;
    }

    // ──────────────────────────────────────────────────────────────
    //  Git: leer commits
    // ──────────────────────────────────────────────────────────────

    private function getCommitsSinceLastTag(): array
    {
        $lastTag = $this->execGit('describe', '--tags', '--abbrev=0');

        $args = ['log', '--pretty=format:%h|||%s|||%an'];
        if ($lastTag) {
            $args[] = "{$lastTag}..HEAD";
        }

        $output = $this->execGit(...$args);
        if (!$output) {
            return [];
        }

        $commits = [];
        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = explode('|||', $line, 3);
            if (count($parts) === 3) {
                $commits[] = [
                    'hash'    => $parts[0],
                    'subject' => $parts[1],
                    'author'  => $parts[2],
                ];
            }
        }

        return $commits;
    }

    private function groupCommits(array $commits): array
    {
        $grouped = array_fill_keys(array_keys(self::CATEGORIES), []);

        foreach ($commits as $commit) {
            $subject = $commit['subject'];

            // Omitir commits de release y commits sin prefijo convencional
            if (preg_match('/^chore\(release\)|^release:/i', $subject)) {
                continue;
            }

            // Solo incluir commits con prefijo Conventional Commits reconocido
            if (!preg_match('/^(feat|fix|perf|refactor|docs|test|chore|style|ci)(\(.+\))?!?:\s*(.+)$/i', $subject, $m)) {
                continue;
            }

            $category = strtolower($m[1]);
            $message  = $m[3];

            $grouped[$category][] = [
                'hash'    => $commit['hash'],
                'subject' => $message,
            ];
        }

        return array_filter($grouped, fn($items) => !empty($items));
    }

    // ──────────────────────────────────────────────────────────────
    //  Generación de changelog
    // ──────────────────────────────────────────────────────────────

    private function showChangelog(string $version, array $grouped): void
    {
        $this->info('');
        $this->info('┌───────────────────────────────────────────────────────────┐');
        $this->info("│  Preview changelog → v{$version}");
        $this->info('└───────────────────────────────────────────────────────────┘');

        if (empty($grouped)) {
            $this->warn('   No se encontraron commits desde el último tag.');
            return;
        }

        foreach ($grouped as $category => $items) {
            $label = self::CATEGORIES[$category] ?? 'Otros';
            $this->info('');
            $this->line("  <fg=yellow>{$label}</>");
            foreach ($items as $item) {
                $this->line("    • {$item['subject']} <fg=gray>({$item['hash']})</>");
            }
        }

        $this->info('');
    }

    private function buildChangelogEntry(string $version, string $date, array $grouped): string
    {
        $lines = ["## [{$version}] - {$date}", ''];

        if (empty($grouped)) {
            $lines[] = '- Sin cambios registrados en esta versión.';
            $lines[] = '';
        } else {
            foreach ($grouped as $category => $items) {
                $label   = self::CATEGORIES[$category] ?? 'Otros cambios';
                $lines[] = "### {$label}";
                $lines[] = '';
                foreach ($items as $item) {
                    $lines[] = "- {$item['subject']}";
                }
                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }

    // ──────────────────────────────────────────────────────────────
    //  Actualización de ficheros
    // ──────────────────────────────────────────────────────────────

    private function updateVersionFile(string $path, string $version): void
    {
        File::put($path, $version . "\n");
        $this->line("   <fg=green>✓</> VERSION → {$version}");
    }

    private function updateRootChangelog(string $entry): void
    {
        $path = base_path('CHANGELOG.md');

        if (File::exists($path)) {
            $content    = File::get($path);
            $insertPos  = strpos($content, "\n## [");

            if ($insertPos !== false) {
                // Insertar antes de la primera entrada existente
                $content = substr($content, 0, $insertPos + 1)
                    . $entry . "\n---\n\n"
                    . substr($content, $insertPos + 1);
            } else {
                $content .= "\n" . $entry;
            }

            File::put($path, $content);
        } else {
            $header = "# Changelog\n\nTodos los cambios notables se documentan en este archivo.\n\n";
            File::put($path, $header . $entry);
        }

        $this->line('   <fg=green>✓</> CHANGELOG.md actualizado');
    }

    private function updateDocsChangelog(string $version, string $date, array $grouped): void
    {
        $path = public_path('docs/versiones_changelog.md');

        if (!File::exists($path)) {
            return;
        }

        $entry     = $this->buildChangelogEntry($version, $date, $grouped);
        $content   = File::get($path);
        $insertPos = strpos($content, "\n## [");

        if ($insertPos !== false) {
            $content = substr($content, 0, $insertPos + 1)
                . $entry . "\n---\n\n"
                . substr($content, $insertPos + 1);
        } else {
            $content .= "\n" . $entry;
        }

        File::put($path, $content);
        $this->line('   <fg=green>✓</> docs/versiones_changelog.md actualizado');
    }

    // ──────────────────────────────────────────────────────────────
    //  Git: commit y tag
    // ──────────────────────────────────────────────────────────────

    private function gitCommitAndTag(string $version): void
    {
        $filesToStage = array_filter([
            base_path('VERSION'),
            base_path('CHANGELOG.md'),
            public_path('docs/versiones_changelog.md'),
        ], fn($f) => File::exists($f));

        foreach ($filesToStage as $file) {
            $this->execGit('add', $file);
        }

        if ($this->option('amend')) {
            // Enmendar el commit existente (invocado desde el git hook)
            $this->execGit('commit', '--amend', '-m', "chore(release): v{$version}");
            $this->line("   <fg=green>✓</> Commit enmendado → chore(release): v{$version}");
        } else {
            $this->execGit('commit', '-m', "chore(release): v{$version}");
            $this->line("   <fg=green>✓</> Commit creado → chore(release): v{$version}");
        }

        $this->execGit('tag', '-a', "v{$version}", '-m', "Release v{$version}");
        $this->line("   <fg=green>✓</> Tag <fg=cyan>v{$version}</> creado");
    }

    /**
     * Ejecuta un comando git y devuelve su salida estándar.
     * Usa proc_open con array para que sea compatible con Windows sin necesidad de shell.
     */
    private function execGit(string ...$args): ?string
    {
        $cmd = array_merge(['git'], array_values($args));

        $process = proc_open(
            $cmd,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            base_path()
        );

        if (!is_resource($process)) {
            return null;
        }

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return trim($output) ?: null;
    }

    // ──────────────────────────────────────────────────────────────
    //  Tests
    // ──────────────────────────────────────────────────────────────

    /**
     * Ejecuta la suite de tests con salida en tiempo real.
     * Devuelve true si todos los tests pasaron (exit code 0).
     */
    private function runTestsSuite(): bool
    {
        $cmd = [PHP_BINARY, base_path('artisan'), 'test'];

        $process = proc_open(
            $cmd,
            [0 => STDIN, 1 => STDOUT, 2 => STDERR],
            $pipes,
            base_path()
        );

        if (!is_resource($process)) {
            $this->error('No se pudo iniciar el proceso de tests.');
            return false;
        }

        $exitCode = proc_close($process);

        return $exitCode === 0;
    }

    // ──────────────────────────────────────────────────────────────
    //  UI helpers
    // ──────────────────────────────────────────────────────────────

    private function showBanner(): void
    {
        $this->info('');
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info('║           TALLER EMPRESARIAL 2.0 - PUBLICAR VERSIÓN       ║');
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->info('');
    }

    private function showSuccess(string $version): void
    {
        $this->info('');
        $this->info('╔═══════════════════════════════════════════════════════════╗');
        $this->info("║         ✓  Versión v{$version} publicada con éxito         ║");
        $this->info('╚═══════════════════════════════════════════════════════════╝');
        $this->info('');
        $this->line('  Para subir al repositorio remoto:');
        $this->line('    <fg=cyan>git push && git push --tags</>');
        $this->info('');
    }
}
