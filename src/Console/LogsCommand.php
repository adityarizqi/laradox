<?php

namespace Laradox\Console;

use Illuminate\Console\Command;

class LogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradox:logs 
                            {service? : Service name (nginx, php, node, scheduler, queue)}
                            {--environment=development : Environment (development|production)}
                            {--f|follow : Follow log output}
                            {--tail= : Number of lines to show from the end of the logs}
                            {--timestamps : Show timestamps}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View Laradox Docker container logs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $env = $this->option('environment');
        $composeFile = base_path("docker-compose.{$env}.yml");

        if (!file_exists($composeFile)) {
            $this->error("Docker Compose file not found: {$composeFile}");
            return self::FAILURE;
        }

        // Check if containers are running
        if (!$this->areContainersRunning($composeFile)) {
            $this->newLine();
            $this->error('✗ No containers are running!');
            $this->line('Start containers with: php artisan laradox:up --detach');
            $this->newLine();
            return self::FAILURE;
        }

        $service = $this->argument('service');

        // If no service specified, show all available services
        if (!$service) {
            $this->info('Available services:');
            $services = $this->getAvailableServices($composeFile);
            foreach ($services as $serviceName) {
                $this->line("  - {$serviceName}");
            }
            $this->newLine();
            $this->comment('Usage: php artisan laradox:logs [service]');
            $this->comment('Example: php artisan laradox:logs php --follow');
            $this->newLine();
            return self::SUCCESS;
        }

        // Build the logs command
        $command = sprintf(
            'docker compose -f %s logs',
            escapeshellarg($composeFile)
        );

        if ($this->option('follow')) {
            $command .= ' --follow';
        }

        if ($this->option('tail')) {
            $command .= sprintf(' --tail=%s', escapeshellarg($this->option('tail')));
        }

        if ($this->option('timestamps')) {
            $command .= ' --timestamps';
        }

        $command .= ' ' . escapeshellarg($service);

        $this->info("Viewing logs for '{$service}' service...");
        $this->line($this->option('follow') ? 'Press Ctrl+C to stop following logs' : '');
        $this->newLine();

        passthru($command, $returnCode);

        return $returnCode === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Check if containers are running for the given compose file.
     *
     * @param string $composeFile
     * @return bool
     */
    protected function areContainersRunning(string $composeFile): bool
    {
        $command = sprintf(
            'docker compose -f %s ps --quiet 2>/dev/null',
            escapeshellarg($composeFile)
        );

        exec($command, $output, $returnCode);

        return $returnCode === 0 && !empty($output);
    }

    /**
     * Get list of available services from the compose file.
     *
     * @param string $composeFile
     * @return array
     */
    protected function getAvailableServices(string $composeFile): array
    {
        $command = sprintf(
            'docker compose -f %s config --services 2>/dev/null',
            escapeshellarg($composeFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || empty($output)) {
            return ['nginx', 'php', 'node', 'scheduler', 'queue'];
        }

        return $output;
    }
}
