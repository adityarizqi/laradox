<?php

namespace Laradox\Console;

use Illuminate\Console\Command;
use Laradox\Console\Concerns\ChecksDocker;

class DownCommand extends Command
{
    use ChecksDocker;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradox:down 
                            {--f|file= : Custom Docker Compose file path}
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
        $customFile = $this->option('file');
        
        // Determine compose file path
        $composeFile = $this->resolveComposeFile($env, $customFile);
        if ($composeFile === false) {
            return self::FAILURE;
        }

        if (!file_exists($composeFile)) {
            $this->error("Docker Compose file not found: {$composeFile}");
            return self::FAILURE;
        }

        $composeFileName = basename($composeFile);
        $this->info("Stopping Laradox using {$composeFileName}...");

        $dockerCompose = $this->getDockerComposeCommand();
        $command = sprintf(
            '%s -f %s down',
            $dockerCompose,
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
