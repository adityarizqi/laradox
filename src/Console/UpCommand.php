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
                            {--build : Build images before starting}
                            {--force-ssl= : Force SSL usage (true=HTTPS required, false=HTTP only, default=auto-detect)}';

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

    /**
     * Determine which nginx configuration to use based on environment and SSL availability.
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
     * Copy the appropriate nginx configuration file.
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
}
