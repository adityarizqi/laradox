<?php

namespace Laradox\Console;

use Illuminate\Console\Command;
use Laradox\Console\Concerns\ChecksDocker;

class UpCommand extends Command
{
    use ChecksDocker;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradox:up 
                            {--environment=development : Environment (development|production)}
                            {--d|detach : Run in detached mode}
                            {--build : Build images before starting}
                            {--force-ssl= : Force SSL usage (true=HTTPS required, false=HTTP only, default=auto-detect)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Laradox Docker containers';

    /**
     * Start or restart Laradox Docker containers using the selected environment, SSL settings, and command options.
     *
     * Determines the appropriate nginx configuration based on SSL presence and the --force-ssl option, copies that configuration,
     * and then either starts containers with `docker compose up` (optionally building and/or detaching) or restarts running containers
     * after optional user confirmation. May prompt the user for decisions when SSL is missing in development or when restarting.
     *
     * @return int Command exit status: `self::SUCCESS` on success, `self::FAILURE` on failure.
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

        // Check if SSL certificates exist
        $certPath = config('laradox.ssl.cert_path');
        $keyPath = config('laradox.ssl.key_path');
        $sslExists = file_exists($certPath) && file_exists($keyPath);

        // Get force-ssl flag value
        $forceSsl = $this->option('force-ssl');

        // Determine which nginx config to use
        $nginxConfigSource = $this->determineNginxConfig($env, $sslExists, $forceSsl);
        if ($nginxConfigSource === false) {
            return self::FAILURE;
        }

        // Copy the appropriate nginx config
        $this->copyNginxConfig($nginxConfigSource);

        // Check if containers are already running
        if ($this->areContainersRunning($composeFile)) {
            $this->newLine();
            $this->warn('⚠ Containers are already running!');
            $this->line('Use "php artisan laradox:down" to stop them first, or restart with "docker compose restart"');
            $this->newLine();
            
            if (!$this->confirm('Do you want to restart the containers?', false)) {
                $this->info('Cancelled.');
                return self::SUCCESS;
            }
            
            $this->info('Restarting containers...');
            $restartCommand = sprintf('docker compose -f %s restart', escapeshellarg($composeFile));
            passthru($restartCommand, $returnCode);
            
            if ($returnCode === 0) {
                $this->newLine();
                $this->info('✓ Containers restarted successfully!');
                $domain = config('laradox.domain');
                $protocol = $sslExists && $forceSsl !== 'false' ? 'https' : 'http';
                $this->line("Visit: {$protocol}://{$domain}");
            }
            
            return $returnCode === 0 ? self::SUCCESS : self::FAILURE;
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
            $protocol = $sslExists && $forceSsl !== 'false' ? 'https' : 'http';
            $this->line("Visit: {$protocol}://{$domain}");
        } elseif ($returnCode !== 0) {
            $this->newLine();
            $this->error('✗ Failed to start containers!');
            $this->line('Stopping any running containers...');
            
            $stopCommand = sprintf('docker compose -f %s down', escapeshellarg($composeFile));
            passthru($stopCommand);
            
            $this->newLine();
            $this->comment('Common issues:');
            $this->line('  - Port conflict: Check if ports are already in use');
            $this->line('  - Permission issues: Ensure Docker has proper permissions');
            $this->line('  - Resource limits: Check Docker resource allocation');
            $this->newLine();
            $this->comment('Check logs with: docker compose -f ' . basename($composeFile) . ' logs');
            $this->newLine();
        }

        return $returnCode === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Choose the nginx configuration file to use based on environment, SSL availability, and the force-ssl option.
     *
     * @param string $env The environment name, typically 'development' or 'production'.
     * @param bool $sslExists True if both SSL certificate and key files exist at configured paths.
     * @param string|null $forceSsl If provided, coerced to boolean: `"true"` requires HTTPS, `"false"` forces HTTP; `null` means auto-detect.
     * @return string|false The chosen nginx config filename (`'app-https.conf'` or `'app-http.conf'`), or `false` when the operation is cancelled or required SSL prerequisites are missing.
     */
    protected function determineNginxConfig(string $env, bool $sslExists, ?string $forceSsl): string|false
    {
        $certPath = config('laradox.ssl.cert_path');
        $keyPath = config('laradox.ssl.key_path');

        // Handle --force-ssl flag
        if ($forceSsl !== null) {
            $forceSslBool = filter_var($forceSsl, FILTER_VALIDATE_BOOLEAN);
            
            if ($forceSslBool) {
                // Force SSL: require certificates
                if (!$sslExists) {
                    $this->newLine();
                    $this->error('✗ SSL is forced but certificates not found!');
                    $this->line('Certificates must exist at:');
                    $this->line("  - {$certPath}");
                    $this->line("  - {$keyPath}");
                    $this->newLine();
                    $this->comment('Generate certificates with: php artisan laradox:setup-ssl');
                    $this->newLine();
                    return false;
                }
                $this->info('✓ Force SSL enabled, using HTTPS configuration');
                return 'app-https.conf';
            } else {
                // Force HTTP only
                $this->warn('⚠ Force HTTP enabled, SSL disabled');
                return 'app-http.conf';
            }
        }

        // Production MUST have SSL (unless explicitly forced to HTTP)
        if ($env === 'production' && !$sslExists) {
            $this->newLine();
            $this->error('✗ SSL certificates are required for production environment!');
            $this->line('Certificates must exist at:');
            $this->line("  - {$certPath}");
            $this->line("  - {$keyPath}");
            $this->newLine();
            $this->comment('Generate certificates with: php artisan laradox:setup-ssl');
            $this->comment('Or use --force-ssl=false to skip SSL (not recommended for production)');
            $this->newLine();
            return false;
        }

        // Development: ask user preference if no SSL
        if ($env === 'development' && !$sslExists) {
            $this->newLine();
            $this->warn('⚠ SSL certificates not found!');
            $this->line('Certificates expected at:');
            $this->line("  - {$certPath}");
            $this->line("  - {$keyPath}");
            $this->newLine();
            $this->comment('Options:');
            $this->line('1. Generate certificates: php artisan laradox:setup-ssl');
            $this->line('2. Continue with HTTP only (port 80)');
            $this->newLine();

            if (!$this->confirm('Do you want to continue with HTTP only?', false)) {
                $this->info('Cancelled. Please setup SSL certificates first.');
                return false;
            }

            $this->warn('Using HTTP-only configuration...');
            return 'app-http.conf';
        }

        // Use HTTPS config when SSL exists
        if ($sslExists) {
            $this->info('✓ SSL certificates found, using HTTPS configuration');
            return 'app-https.conf';
        }

        return 'app-http.conf';
    }

    /**
     * Install the chosen nginx configuration into docker/nginx/conf.d/app.conf.
     *
     * If the named source file does not exist, the method warns and leaves the current
     * configuration unchanged.
     *
     * @param string $configFile Filename of the nginx configuration to copy (e.g. `app-https.conf`).
     */
    protected function copyNginxConfig(string $configFile): void
    {
        $sourcePath = base_path("docker/nginx/conf.d/{$configFile}");
        $targetPath = base_path('docker/nginx/conf.d/app.conf');

        if (!file_exists($sourcePath)) {
            $this->warn("Configuration file not found: {$sourcePath}");
            $this->line('Using existing configuration...');
            return;
        }

        copy($sourcePath, $targetPath);
        $this->line("Using nginx configuration: {$configFile}");
    }

    /**
     * Determine whether any containers defined by the given Docker Compose file are currently running.
     *
     * @param string $composeFile Path to the Docker Compose file to check.
     * @return bool `true` if one or more containers for the compose file are running, `false` otherwise.
     */
    protected function areContainersRunning(string $composeFile): bool
    {
        $command = sprintf(
            'docker compose -f %s ps --quiet 2>/dev/null',
            escapeshellarg($composeFile)
        );

        exec($command, $output, $returnCode);

        // If command succeeds and has output, containers exist
        return $returnCode === 0 && !empty($output);
    }
}