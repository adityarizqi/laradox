<?php

namespace Laradox\Console;

use Illuminate\Console\Command;

class DownCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradox:down 
                            {--environment=development : Environment (development|production)}
                            {--volumes : Remove named volumes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop Laradox Docker containers';

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

        $this->info("Stopping Laradox ({$env} environment)...");

        $command = sprintf(
            'docker compose -f %s down',
            escapeshellarg($composeFile)
        );

        if ($this->option('volumes')) {
            $command .= ' -v';
        }

        $this->line("Executing: {$command}");
        $this->newLine();

        passthru($command, $returnCode);

        if ($returnCode === 0) {
            $this->info('✓ Containers stopped successfully!');
        }

        return $returnCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
