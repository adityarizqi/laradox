<?php

namespace Laradox\Console;

use Illuminate\Console\Command;
use Laradox\Console\Concerns\ChecksDocker;
use Laradox\Console\Concerns\InspectsContainers;

class OptimizeCommand extends Command
{
    use ChecksDocker;
    use InspectsContainers;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradox:optimize
                            {--f|file= : Custom Docker Compose file path}
                            {--environment=production : Environment (development|production)}
                            {--service=php : Service the optimization runs in}
                            {--clear : Clear the cached files instead of building them}
                            {--skip-autoloader : Skip the Composer autoloader dump}
                            {--skip-reload : Skip reloading the Octane workers}
                            {--force : Do not ask for confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build (or clear) the production caches inside the running containers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check if Docker is installed
        if (! $this->checkDocker()) {
            return $this->handleMissingDocker();
        }

        // Check if Docker Compose is available
        if (! $this->checkDockerCompose()) {
            $this->newLine();
            $this->error('✗ Docker Compose is not available.');
            $this->line('Please ensure Docker Compose is installed and running.');
            $this->line('Visit: https://docs.docker.com/compose/install/');
            $this->newLine();

            return self::FAILURE;
        }

        $env = $this->option('environment');
        $customFile = $this->option('file');

        // Determine compose file path
        $composeFile = $this->resolveComposeFile($env, $customFile);
        if ($composeFile === false) {
            return self::FAILURE;
        }

        if (! file_exists($composeFile)) {
            $this->error("Docker Compose file not found: {$composeFile}");

            return self::FAILURE;
        }

        // Check if containers are running
        if (! $this->areContainersRunning($composeFile)) {
            $this->newLine();
            $this->error('✗ No containers are running!');
            $this->line('Start containers with: php artisan laradox:up --detach');
            $this->newLine();

            return self::FAILURE;
        }

        $service = $this->option('service');
        $runningServices = $this->getRunningServices($composeFile);

        if (! in_array($service, $runningServices, true)) {
            $this->error("Service '{$service}' is not running.");
            $this->newLine();

            if ($runningServices !== []) {
                $this->info('Running services:');
                foreach ($runningServices as $runningService) {
                    $this->line("  - {$runningService}");
                }
                $this->newLine();
            }

            return self::FAILURE;
        }

        // Caching the config in development freezes the current .env, which is
        // rarely what someone editing code wants.
        if ($env === 'development' && ! $this->option('clear') && ! $this->confirmDevelopmentCaching()) {
            return self::SUCCESS;
        }

        return $this->runSteps($composeFile, $service, $this->buildSteps($env));
    }

    /**
     * Warn that cached config ignores later .env edits, and ask to continue.
     */
    protected function confirmDevelopmentCaching(): bool
    {
        $this->newLine();
        $this->warn('⚠ Optimizing a development environment caches config, routes and views.');
        $this->line('Changes to .env, routes and Blade templates will be ignored until you run:');
        $this->comment('  php artisan laradox:optimize --clear --environment=development');
        $this->newLine();

        if ($this->option('force') || ! $this->input->isInteractive()) {
            return true;
        }

        if (! $this->confirm('Continue anyway?', false)) {
            $this->info('Cancelled.');

            return false;
        }

        return true;
    }

    /**
     * Build the ordered list of steps for the requested mode.
     *
     * @return array<int, array{label: string, command: array<int, string>, optional?: bool}>
     */
    protected function buildSteps(string $env): array
    {
        $steps = [];

        if ($this->option('clear')) {
            $steps[] = [
                'label' => 'Clearing cached bootstrap files',
                'command' => ['php', 'artisan', 'optimize:clear'],
            ];
        } else {
            if (! $this->option('skip-autoloader')) {
                $autoloader = ['composer', 'dump-autoload', '--optimize', '--classmap-authoritative'];

                // --no-dev would strip the dev autoload map a development
                // container still needs for tests and factories.
                if ($env === 'production') {
                    $autoloader[] = '--no-dev';
                }

                $steps[] = [
                    'label' => 'Dumping optimized Composer autoloader',
                    'command' => $autoloader,
                ];
            }

            $steps[] = ['label' => 'Caching configuration', 'command' => ['php', 'artisan', 'config:cache']];
            $steps[] = ['label' => 'Caching routes', 'command' => ['php', 'artisan', 'route:cache']];
            $steps[] = ['label' => 'Caching views', 'command' => ['php', 'artisan', 'view:cache']];
            $steps[] = ['label' => 'Caching events', 'command' => ['php', 'artisan', 'event:cache']];
        }

        if (! $this->option('skip-reload')) {
            // Octane keeps the old bootstrap cache in memory until the workers
            // are recycled, so this is what actually applies the changes.
            $steps[] = [
                'label' => 'Reloading Octane workers',
                'command' => ['php', 'artisan', 'octane:reload'],
                'optional' => true,
            ];
        }

        return $steps;
    }

    /**
     * Run every step in order, stopping at the first required failure.
     *
     * @param  array<int, array{label: string, command: array<int, string>, optional?: bool}>  $steps
     */
    protected function runSteps(string $composeFile, string $service, array $steps): int
    {
        $this->newLine();
        $this->info($this->option('clear')
            ? "Clearing Laradox caches in '{$service}'..."
            : "Optimizing Laradox in '{$service}'...");
        $this->newLine();

        $results = [];
        $failed = false;

        foreach ($steps as $step) {
            $this->line("→ {$step['label']}");

            $startedAt = microtime(true);
            [$exitCode, $output] = $this->runInContainer($composeFile, $service, $step['command']);
            $duration = round(microtime(true) - $startedAt, 2);

            $optional = $step['optional'] ?? false;

            if ($exitCode === 0) {
                $results[] = [$step['label'], '<info>done</info>', "{$duration}s"];

                continue;
            }

            foreach ($output as $line) {
                $this->line("  {$line}");
            }

            if ($optional) {
                $this->warn("⚠ Skipped: {$step['label']} (command unavailable or failed)");
                $results[] = [$step['label'], '<comment>skipped</comment>', "{$duration}s"];

                continue;
            }

            $results[] = [$step['label'], '<error>failed</error>', "{$duration}s"];
            $failed = true;

            break;
        }

        $this->newLine();
        $this->table(['Step', 'Result', 'Duration'], $results);

        if ($failed) {
            $this->error('✗ Optimization failed.');
            $this->newLine();

            return self::FAILURE;
        }

        $this->info($this->option('clear')
            ? '✓ Caches cleared successfully!'
            : '✓ Optimization completed successfully!');
        $this->newLine();

        return self::SUCCESS;
    }
}
