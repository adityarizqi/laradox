<?php

namespace Laradox\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradox:install {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Laradox Docker environment for Laravel';

    /**
     * Installs the Laradox Docker environment and outputs next-step instructions.
     *
     * Publishes Laradox vendor assets (honoring the command's --force option), ensures helper scripts are executable,
     * creates the docker/nginx/ssl directory when missing, and prints installation success and follow-up commands.
     *
     * @return int The command exit status code.
     */
    public function handle(): int
    {
        $this->info('Installing Laradox...');

        // Publish all files
        $this->call('vendor:publish', [
            '--tag' => 'laradox',
            '--force' => $this->option('force'),
        ]);

        // Make scripts executable
        $this->makeScriptsExecutable();

        // Create SSL directory if it doesn't exist
        $sslDir = base_path('docker/nginx/ssl');
        if (!File::exists($sslDir)) {
            File::makeDirectory($sslDir, 0755, true);
            $this->info('Created SSL directory: docker/nginx/ssl');
        }

        $this->newLine();
        $this->info('✓ Laradox installed successfully!');
        $this->newLine();

        $this->comment('Next steps:');
        $this->line('1. Setup SSL: php artisan laradox:setup-ssl');
        $this->line('   Note: SSL is optional for development but REQUIRED for production');
        $this->line('2. Run: php artisan laradox:up');
        $this->line('3. Install dependencies: ./composer install && ./npm install');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Make helper scripts executable.
     */
    protected function makeScriptsExecutable(): void
    {
        $scripts = ['composer', 'npm', 'php'];

        foreach ($scripts as $script) {
            $path = base_path($script);
            if (File::exists($path)) {
                chmod($path, 0755);
                $this->info("Made {$script} executable");
            }
        }
    }
}