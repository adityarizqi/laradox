<?php

namespace Laradox\Console;

use Illuminate\Console\Command;

class UpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradox:up 
                            {--environment=development : Environment (development|production)}
                            {--d|detach : Run in detached mode}
                            {--build : Build images before starting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Laradox Docker containers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $env = $this->option('environment');
        $composeFile = base_path("docker-compose.{$env}.yml");

        if (!file_exists($composeFile)) {
            $this->error("Docker Compose file not found: {$composeFile}");
            return self::FAILURE;
        }

        $this->info("Starting Laradox ({$env} environment)...");

        $command = sprintf(
            'docker compose -f %s up',
            escapeshellarg($composeFile)
        );

        if ($this->option('build')) {
            $command .= ' --build';
        }

        if ($this->option('detach')) {
            $command .= ' -d';
        }

        $this->line("Executing: {$command}");
        $this->newLine();

        passthru($command, $returnCode);

        if ($returnCode === 0 && $this->option('detach')) {
            $this->newLine();
            $this->info('✓ Containers started successfully!');
            $domain = config('laradox.domain');
            $this->line("Visit: https://{$domain}");
        }

        return $returnCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
