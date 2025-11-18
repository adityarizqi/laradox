<?php

namespace Laradox\Tests\Feature;

use Illuminate\Support\Facades\File;
use Laradox\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class InstallCommandTest extends FeatureTestCase
{
    #[Test]
    public function it_can_install_laradox(): void
    {
        $this->artisan('laradox:install')
            ->expectsOutput('Installing Laradox...')
            ->expectsOutput('✓ Laradox installed successfully!')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_creates_ssl_directory(): void
    {
        $this->artisan('laradox:install')->assertExitCode(0);

        $sslDir = base_path('docker/nginx/ssl');
        $this->assertTrue(File::exists($sslDir));
        $this->assertTrue(File::isDirectory($sslDir));
    }

    #[Test]
    public function it_publishes_configuration_file(): void
    {
        $this->artisan('laradox:install')->assertExitCode(0);

        $this->assertTrue(File::exists(config_path('laradox.php')));
    }

    #[Test]
    public function it_publishes_docker_compose_files(): void
    {
        $this->artisan('laradox:install')->assertExitCode(0);

        $this->assertTrue(File::exists(base_path('docker-compose.development.yml')));
        $this->assertTrue(File::exists(base_path('docker-compose.production.yml')));
    }

    #[Test]
    public function it_publishes_helper_scripts(): void
    {
        $this->artisan('laradox:install')->assertExitCode(0);

        $this->assertTrue(File::exists(base_path('composer')));
        $this->assertTrue(File::exists(base_path('npm')));
        $this->assertTrue(File::exists(base_path('php')));
    }

    #[Test]
    public function it_makes_scripts_executable(): void
    {
        $this->artisan('laradox:install')->assertExitCode(0);

        $scripts = ['composer', 'npm', 'php'];

        foreach ($scripts as $script) {
            $path = base_path($script);
            if (File::exists($path)) {
                $perms = fileperms($path);
                $this->assertTrue(
                    ($perms & 0111) !== 0,
                    "Script {$script} is not executable"
                );
            }
        }
    }

    #[Test]
    public function it_can_force_overwrite_existing_files(): void
    {
        // Create a dummy file
        $configPath = config_path('laradox.php');
        File::ensureDirectoryExists(dirname($configPath));
        File::put($configPath, '<?php return [];');

        $this->artisan('laradox:install', ['--force' => true])
            ->assertExitCode(0);

        // Verify file was overwritten
        $this->assertStringContainsString('Domain Configuration', File::get($configPath));
    }

    #[Test]
    public function it_displays_next_steps(): void
    {
        $this->artisan('laradox:install')
            ->expectsOutput('Next steps:')
            ->expectsOutputToContain('Setup SSL')
            ->expectsOutputToContain('php artisan laradox:up')
            ->assertExitCode(0);
    }
}
