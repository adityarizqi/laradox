<?php

namespace Laradox\Console;

use Illuminate\Console\Command;
use Laradox\Console\Concerns\ChecksDocker;
use Laradox\Console\Concerns\InspectsContainers;

class StatusCommand extends Command
{
    use ChecksDocker;
    use InspectsContainers;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradox:status
                            {service? : Limit the report to a single service}
                            {--f|file= : Custom Docker Compose file path}
                            {--environment=development : Environment (development|production)}
                            {--stats : Include CPU, memory and I/O usage per container}
                            {--watch : Refresh the report until interrupted}
                            {--interval=5 : Seconds between refreshes when watching}
                            {--json : Output the report as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show Laradox service health and resource usage';

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

        if ($this->option('watch')) {
            return $this->watch($composeFile, $env);
        }

        return $this->report($composeFile, $env);
    }

    /**
     * Re-render the report on an interval until the user interrupts.
     */
    protected function watch(string $composeFile, string $env): int
    {
        if ($this->option('json')) {
            $this->error('The --watch option cannot be combined with --json.');

            return self::FAILURE;
        }

        $interval = $this->option('interval');

        if (! is_numeric($interval) || (int) $interval < 1) {
            $this->error('The --interval option must be a positive integer.');

            return self::FAILURE;
        }

        $interval = (int) $interval;

        while (true) {
            // Clear the screen and park the cursor at the top-left, so the
            // table refreshes in place instead of scrolling away.
            $this->output->write("\033[2J\033[H");

            $this->report($composeFile, $env);

            $this->line("Refreshing every {$interval}s — press Ctrl+C to stop.");

            sleep($interval);
        }
    }

    /**
     * Render a single status report.
     *
     * @return int SUCCESS when every reported service is running and healthy
     */
    protected function report(string $composeFile, string $env): int
    {
        $statuses = $this->getServiceStatuses($composeFile);
        $service = $this->argument('service');

        if ($service !== null) {
            $filtered = array_values(array_filter(
                $statuses,
                fn (array $row): bool => $row['service'] === $service
            ));

            if ($filtered === []) {
                $this->error("Service '{$service}' is not defined in {$env} environment.");
                $this->newLine();
                $this->info('Available services:');
                foreach ($statuses as $row) {
                    $this->line("  - {$row['service']}");
                }
                $this->newLine();

                return self::FAILURE;
            }

            $statuses = $filtered;
        }

        if ($statuses === []) {
            if ($this->option('json')) {
                $this->outputJson($composeFile, $env, []);

                return self::FAILURE;
            }

            $this->newLine();
            $this->warn('⚠ No services found in '.basename($composeFile).'.');
            $this->newLine();

            return self::FAILURE;
        }

        $usage = $this->option('stats')
            ? $this->getResourceUsage($this->containerIdsFrom($statuses))
            : [];

        $rows = [];

        foreach ($statuses as $status) {
            $rows[] = $status + ['usage' => $usage[$status['container']] ?? null];
        }

        if ($this->option('json')) {
            $this->outputJson($composeFile, $env, $rows);

            return $this->exitCodeFor($statuses);
        }

        $this->renderTable($composeFile, $env, $rows);

        return $this->exitCodeFor($statuses);
    }

    /**
     * Render the report as a table with a one-line summary.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function renderTable(string $composeFile, string $env, array $rows): void
    {
        $this->newLine();
        $this->info('Laradox status — '.basename($composeFile)." ({$env})");
        $this->newLine();

        $headers = ['Service', 'State', 'Health', 'Uptime', 'Ports'];

        if ($this->option('stats')) {
            $headers = array_merge($headers, ['CPU', 'Memory', 'Net I/O']);
        }

        $tableRows = [];

        foreach ($rows as $row) {
            $cells = [
                $row['service'],
                $this->decorateState($row['state'], $row['exit_code']),
                $this->decorateHealth($row['health']),
                $row['uptime'],
                $row['ports'],
            ];

            if ($this->option('stats')) {
                $usage = $row['usage'];
                $cells = array_merge($cells, [
                    $usage['cpu'] ?? '-',
                    $usage['memory'] ?? '-',
                    $usage['network'] ?? '-',
                ]);
            }

            $tableRows[] = $cells;
        }

        $this->table($headers, $tableRows);

        $running = count(array_filter($rows, fn (array $row): bool => $row['state'] === 'running'));
        $ready = count(array_filter($rows, fn (array $row): bool => $this->serviceIsReady($row)));
        $total = count($rows);

        // "ready" rather than "healthy": a service without a healthcheck is
        // reported as n/a, and calling that healthy would be a claim too far.
        $this->line(sprintf(
            '%d service%s · %d running · %d ready',
            $total,
            $total === 1 ? '' : 's',
            $running,
            $ready
        ));

        if ($running === 0) {
            $this->newLine();
            $this->comment('Start the containers with: php artisan laradox:up --detach');
        }

        if (! $this->option('stats') && $running > 0) {
            $this->comment('Add --stats for CPU and memory usage, or --watch to keep the report live.');
        }

        $this->newLine();
    }

    /**
     * Print the report as JSON.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function outputJson(string $composeFile, string $env, array $rows): void
    {
        $this->line((string) json_encode([
            'environment' => $env,
            'compose_file' => $composeFile,
            'services' => $rows,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Collect the container names present in a status snapshot.
     *
     * @param  array<int, array<string, mixed>>  $statuses
     * @return array<int, string>
     */
    protected function containerIdsFrom(array $statuses): array
    {
        return array_values(array_filter(array_map(
            fn (array $row) => $row['container'],
            $statuses
        )));
    }

    /**
     * Determine the exit code for a snapshot, so CI can gate on it.
     *
     * @param  array<int, array<string, mixed>>  $statuses
     */
    protected function exitCodeFor(array $statuses): int
    {
        foreach ($statuses as $status) {
            if (! $this->serviceIsReady($status)) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * Colourise a container state.
     */
    protected function decorateState(string $state, ?int $exitCode): string
    {
        if ($state === 'running') {
            return "<info>{$state}</info>";
        }

        if ($state === 'exited' && $exitCode !== null && $exitCode !== 0) {
            return "<error>exited ({$exitCode})</error>";
        }

        return "<comment>{$state}</comment>";
    }

    /**
     * Colourise a healthcheck result.
     */
    protected function decorateHealth(string $health): string
    {
        return match ($health) {
            'healthy' => "<info>{$health}</info>",
            'unhealthy' => "<error>{$health}</error>",
            'n/a' => $health,
            default => "<comment>{$health}</comment>",
        };
    }
}
