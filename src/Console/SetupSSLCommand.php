<?php

namespace Laradox\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupSSLCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradox:setup-ssl 
                            {--domain= : Primary domain (default: from config)}
                            {--additional-domains=* : Additional domains to include}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate SSL certificates using mkcert';

    /**
     * Generate SSL certificates for the configured domain(s) using mkcert.
     *
     * Creates the SSL directory if necessary, executes mkcert to produce the certificate and key files,
     * and writes progress and error messages to the console. If mkcert is not available, emits guidance and exits with failure.
     *
     * @return int Exit status: `0` on success, non-zero on failure.
     */
    public function handle(): int
    {
        $this->info('Setting up SSL certificates...');

        // Check if mkcert is installed
        if (!$this->checkMkcert()) {
            return $this->handleMissingMkcert();
        }

        $domain = $this->option('domain') ?: config('laradox.domain');
        $additionalDomains = $this->option('additional-domains') ?: config('laradox.additional_domains', []);
        
        $domains = array_merge([$domain], $additionalDomains);
        $domainsString = implode(' ', array_map('escapeshellarg', $domains));

        $certPath = config('laradox.ssl.cert_path');
        $keyPath = config('laradox.ssl.key_path');

        // Ensure SSL directory exists
        $sslDir = dirname($certPath);
        if (!File::exists($sslDir)) {
            File::makeDirectory($sslDir, 0755, true);
        }

        $command = sprintf(
            'mkcert -install -cert-file %s -key-file %s %s',
            escapeshellarg($certPath),
            escapeshellarg($keyPath),
            $domainsString
        );

        $this->line("Executing: {$command}");
        $this->newLine();

        exec($command, $output, $returnCode);

        if ($returnCode === 0) {
            $this->info('✓ SSL certificates generated successfully!');
            $this->line("Certificate: {$certPath}");
            $this->line("Key: {$keyPath}");
            $this->line("Domains: " . implode(', ', $domains));
            return self::SUCCESS;
        }

        $this->error('Failed to generate SSL certificates.');
        foreach ($output as $line) {
            $this->line($line);
        }

        return self::FAILURE;
    }

    /**
     * Check if mkcert is available.
     */
    protected function checkMkcert(): bool
    {
        exec('which mkcert', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Handle missing mkcert installation.
     *
     * @return int
     */
    protected function handleMissingMkcert(): int
    {
        $this->newLine();
        $this->warn('⚠ mkcert is not installed or not in PATH.');
        $this->line('SSL certificates are optional. You can run Laradox without HTTPS.');
        $this->newLine();

        $os = $this->detectOS();

        if ($os === 'linux') {
            if ($this->confirm('Would you like to install mkcert automatically?', true)) {
                return $this->installMkcertLinux();
            }
        } elseif ($os === 'macos') {
            if ($this->confirm('Would you like to install mkcert using Homebrew?', true)) {
                return $this->installMkcertMacOS();
            }
        } elseif ($os === 'windows') {
            $this->info('For Windows, please download the mkcert executable:');
            $this->line('1. Visit: https://github.com/FiloSottile/mkcert/releases');
            $this->line('2. Download the latest Windows executable (mkcert-v*-windows-amd64.exe)');
            $this->line('3. Rename it to mkcert.exe and add to your PATH');
            $this->newLine();
            if ($this->confirm('Open the download page in your browser?', true)) {
                $this->openURL('https://github.com/FiloSottile/mkcert/releases');
            }
        } else {
            $this->line('To enable HTTPS, install mkcert from: https://github.com/FiloSottile/mkcert/releases');
        }

        $this->newLine();
        $this->line('After installation, run this command again: php artisan laradox:setup-ssl');
        $this->newLine();

        return self::FAILURE;
    }

    /**
     * Detect the operating system.
     *
     * @return string 'linux', 'macos', 'windows', or 'unknown'
     */
    protected function detectOS(): string
    {
        $os = strtolower(PHP_OS);

        if (stripos($os, 'linux') !== false) {
            return 'linux';
        } elseif (stripos($os, 'darwin') !== false) {
            return 'macos';
        } elseif (stripos($os, 'win') !== false) {
            return 'windows';
        }

        return 'unknown';
    }

    /**
     * Install mkcert on Linux.
     *
     * @return int
     */
    protected function installMkcertLinux(): int
    {
        $this->info('Installing mkcert on Linux...');
        $this->newLine();

        // Detect package manager
        $hasApt = $this->commandExists('apt-get');
        $hasYum = $this->commandExists('yum');
        $hasDnf = $this->commandExists('dnf');

        if ($hasApt) {
            $this->line('Using apt package manager...');
            $commands = [
                'sudo apt-get update',
                'sudo apt-get install -y mkcert',
            ];
        } elseif ($hasDnf) {
            $this->line('Using dnf package manager...');
            $commands = [
                'sudo dnf install -y mkcert',
            ];
        } elseif ($hasYum) {
            $this->line('Using yum package manager...');
            $commands = [
                'sudo yum install -y mkcert',
            ];
        } else {
            $this->error('Could not detect package manager (apt, yum, or dnf).');
            $this->line('Please install mkcert manually: https://github.com/FiloSottile/mkcert');
            return self::FAILURE;
        }

        foreach ($commands as $command) {
            $this->line("Running: {$command}");
            passthru($command, $returnCode);
            if ($returnCode !== 0) {
                $this->error('Installation failed.');
                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('✓ mkcert installed successfully!');
        $this->line('Running SSL setup...');
        $this->newLine();

        // Re-run the SSL setup now that mkcert is installed
        return $this->handle();
    }

    /**
     * Install mkcert on macOS using Homebrew.
     *
     * @return int
     */
    protected function installMkcertMacOS(): int
    {
        if (!$this->commandExists('brew')) {
            $this->error('Homebrew is not installed.');
            $this->line('Please install Homebrew first: https://brew.sh');
            $this->line('Or install mkcert manually: https://github.com/FiloSottile/mkcert');
            return self::FAILURE;
        }

        $this->info('Installing mkcert using Homebrew...');
        $this->newLine();

        $command = 'brew install mkcert';
        $this->line("Running: {$command}");
        passthru($command, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Installation failed.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✓ mkcert installed successfully!');
        $this->line('Running SSL setup...');
        $this->newLine();

        // Re-run the SSL setup now that mkcert is installed
        return $this->handle();
    }

    /**
     * Check if a command exists.
     *
     * @param string $command
     * @return bool
     */
    protected function commandExists(string $command): bool
    {
        exec("which {$command}", $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Open a URL in the default browser.
     *
     * @param string $url
     * @return void
     */
    protected function openURL(string $url): void
    {
        $os = $this->detectOS();

        if ($os === 'macos') {
            exec("open " . escapeshellarg($url));
        } elseif ($os === 'linux') {
            exec("xdg-open " . escapeshellarg($url));
        } elseif ($os === 'windows') {
            exec("start " . escapeshellarg($url));
        }
    }
}