<?php

namespace Laradox\Console;

use Illuminate\Console\Command;
use Laradox\Console\Concerns\ChecksDocker;

class ShellCommand extends Command
{
    use ChecksDocker;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradox:shell 
                            {service=php : Service name (nginx, php, node, scheduler, queue)}
                            {--environment=development : Environment (development|production)}
                            {--user= : User to run the shell as}
                            {--shell=bash : Shell to use (bash, sh, zsh)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enter a container interactively';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // Check if Docker is installed
        if (!$this->checkDocker()) {
            return $this->handleMissingDocker();
        }

        // Check if Docker Compose is available
        if (!$this->checkDockerCompose()) {
            $this->newLine();
            $this->error('✗ Docker Compose is not available.');
            $this->line('Please ensure Docker Compose is installed and running.');
            $this->line('Visit: https://docs.docker.com/compose/install/');
            $this->newLine();
            return self::FAILURE;
        }

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

        // Validate service exists
        $availableServices = $this->getAvailableServices($composeFile);
        if (!in_array($service, $availableServices)) {
            $this->error("Service '{$service}' not found in {$env} environment.");
            $this->newLine();
            $this->info('Available services:');
            foreach ($availableServices as $serviceName) {
                $this->line("  - {$serviceName}");
            }
            $this->newLine();
            return self::FAILURE;
        }

        // Check if service is running
        if (!$this->isServiceRunning($composeFile, $service)) {
            $this->error("Service '{$service}' is not running.");
            $this->line('Start containers with: php artisan laradox:up --detach');
            return self::FAILURE;
        }

        // Build the shell command
        $shell = $this->option('shell');
        $command = sprintf(
            'docker compose -f %s exec',
            escapeshellarg($composeFile)
        );

        // Add user option if provided
        if ($this->option('user')) {
            $command .= sprintf(' --user=%s', escapeshellarg($this->option('user')));
        }

        $command .= sprintf(' %s %s', escapeshellarg($service), escapeshellarg($shell));

        $this->info("Entering '{$service}' container with {$shell}...");
        $this->comment('Type "exit" to leave the container shell.');
        $this->newLine();

        passthru($command, $returnCode);

        return $returnCode === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Check if a specific service is running.
     *
     * @param string $composeFile
     * @param string $service
     * @return bool
     */
    protected function isServiceRunning(string $composeFile, string $service): bool
    {
        $command = sprintf(
            'docker compose -f %s ps --services --filter "status=running" 2>/dev/null',
            escapeshellarg($composeFile)
        );

        exec($command, $output, $returnCode);

        return $returnCode === 0 && in_array($service, $output);
    }
}
