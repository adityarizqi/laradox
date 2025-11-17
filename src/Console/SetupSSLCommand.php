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
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Setting up SSL certificates...');

        // Check if mkcert is installed
        if (!$this->checkMkcert()) {
            $this->error('mkcert is not installed or not in PATH.');
            $this->line('Please install mkcert from: https://github.com/FiloSottile/mkcert/releases');
            return self::FAILURE;
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
}
