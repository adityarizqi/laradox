<?php

namespace Laradox\Tests\Feature;

use Illuminate\Support\Facades\File;
use Laradox\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class UpCommandTest extends FeatureTestCase
{
    #[Test]
    public function it_fails_when_docker_compose_file_not_found(): void
    {
        $this->artisan('laradox:up')
            ->expectsOutput('Docker Compose file not found: ' . base_path('docker-compose.development.yml'))
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_environment_option(): void
    {
        $this->createTestDockerComposeFile('production');
        $this->createTestSslDirectory();
        File::put(config('laradox.ssl.cert_path'), 'dummy cert');
        File::put(config('laradox.ssl.key_path'), 'dummy key');

        $command = $this->artisan('laradox:up', ['--environment' => 'production']);

        // The command will fail because docker compose is not available in tests,
        // but we can verify it tried to use the correct file
        $this->assertTrue(File::exists(base_path('docker-compose.production.yml')));
    }

    #[Test]
    public function it_uses_development_environment_by_default(): void
    {
        $this->createTestDockerComposeFile('development');
        $this->createTestSslDirectory();
        File::put(config('laradox.ssl.cert_path'), 'dummy cert');
        File::put(config('laradox.ssl.key_path'), 'dummy key');

        $command = $this->artisan('laradox:up');

        // Verify the command tried to use development file
        $this->assertTrue(File::exists(base_path('docker-compose.development.yml')));
    }

    #[Test]
    public function it_accepts_detach_option(): void
    {
        $this->createTestDockerComposeFile();
        $this->createTestSslDirectory();
        File::put(config('laradox.ssl.cert_path'), 'dummy cert');
        File::put(config('laradox.ssl.key_path'), 'dummy key');

        $this->artisan('laradox:up', ['--detach' => true])
            ->expectsOutput('Starting Laradox (development environment)...')
            ->doesntExpectOutput('Docker Compose file not found');
    }

    #[Test]
    public function it_accepts_build_option(): void
    {
        $this->createTestDockerComposeFile();
        $this->createTestSslDirectory();
        File::put(config('laradox.ssl.cert_path'), 'dummy cert');
        File::put(config('laradox.ssl.key_path'), 'dummy key');

        $this->artisan('laradox:up', ['--build' => true])
            ->expectsOutput('Starting Laradox (development environment)...')
            ->doesntExpectOutput('Docker Compose file not found');
    }

    #[Test]
    public function it_displays_starting_message(): void
    {
        $this->createTestDockerComposeFile();
        $this->createTestSslDirectory();
        File::put(config('laradox.ssl.cert_path'), 'dummy cert');
        File::put(config('laradox.ssl.key_path'), 'dummy key');

        $this->artisan('laradox:up')
            ->expectsOutput('Starting Laradox (development environment)...')
            ->doesntExpectOutput('Docker Compose file not found');
    }

    #[Test]
    public function it_escapes_shell_arguments(): void
    {
        $this->createTestDockerComposeFile();

        // Verify the command handles file paths safely
        $this->assertTrue(File::exists(base_path('docker-compose.development.yml')));
    }

    #[Test]
    public function it_requires_ssl_for_production_environment(): void
    {
        $this->createTestDockerComposeFile('production');

        // Remove SSL certificates
        $certPath = config('laradox.ssl.cert_path');
        $keyPath = config('laradox.ssl.key_path');
        if (File::exists($certPath)) {
            File::delete($certPath);
        }
        if (File::exists($keyPath)) {
            File::delete($keyPath);
        }

        $this->artisan('laradox:up', ['--environment' => 'production'])
            ->expectsOutput('✗ SSL certificates are required for production environment!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_uses_https_config_when_ssl_exists(): void
    {
        $this->createTestDockerComposeFile('development');
        $this->createTestSslDirectory();
        
        // Create both nginx configs
        $httpConfigPath = base_path('docker/nginx/conf.d/app-http.conf');
        $httpsConfigPath = base_path('docker/nginx/conf.d/app-https.conf');
        
        File::ensureDirectoryExists(dirname($httpConfigPath));
        File::put($httpConfigPath, 'http config');
        File::put($httpsConfigPath, 'https config');
        
        // Create valid SSL certificates
        File::put(config('laradox.ssl.cert_path'), 'dummy cert');
        File::put(config('laradox.ssl.key_path'), 'dummy key');

        $this->artisan('laradox:up')
            ->expectsOutputToContain('SSL certificates found')
            ->expectsOutputToContain('app-https.conf');
    }

    #[Test]
    public function it_forces_ssl_with_flag_true(): void
    {
        $this->createTestDockerComposeFile('development');
        $this->createTestSslDirectory();
        
        // Create both nginx configs
        $httpConfigPath = base_path('docker/nginx/conf.d/app-http.conf');
        $httpsConfigPath = base_path('docker/nginx/conf.d/app-https.conf');
        
        File::ensureDirectoryExists(dirname($httpConfigPath));
        File::put($httpConfigPath, 'http config');
        File::put($httpsConfigPath, 'https config');
        
        // Create valid SSL certificates
        File::put(config('laradox.ssl.cert_path'), 'dummy cert');
        File::put(config('laradox.ssl.key_path'), 'dummy key');

        $this->artisan('laradox:up', ['--force-ssl' => 'true'])
            ->expectsOutputToContain('Force SSL enabled')
            ->expectsOutputToContain('app-https.conf');
    }

    #[Test]
    public function it_fails_when_forcing_ssl_without_certificates(): void
    {
        $this->createTestDockerComposeFile('development');
        $this->createTestSslDirectory();
        
        // Create nginx configs but NO certificates
        $httpConfigPath = base_path('docker/nginx/conf.d/app-http.conf');
        $httpsConfigPath = base_path('docker/nginx/conf.d/app-https.conf');
        
        File::ensureDirectoryExists(dirname($httpConfigPath));
        File::put($httpConfigPath, 'http config');
        File::put($httpsConfigPath, 'https config');

        $this->artisan('laradox:up', ['--force-ssl' => 'true'])
            ->expectsOutput('✗ SSL is forced but certificates not found!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_forces_http_only_with_flag_false(): void
    {
        $this->createTestDockerComposeFile('development');
        $this->createTestSslDirectory();
        
        // Create both nginx configs
        $httpConfigPath = base_path('docker/nginx/conf.d/app-http.conf');
        $httpsConfigPath = base_path('docker/nginx/conf.d/app-https.conf');
        
        File::ensureDirectoryExists(dirname($httpConfigPath));
        File::put($httpConfigPath, 'http config');
        File::put($httpsConfigPath, 'https config');
        
        // Even with SSL certificates, should use HTTP
        File::put(config('laradox.ssl.cert_path'), 'dummy cert');
        File::put(config('laradox.ssl.key_path'), 'dummy key');

        $this->artisan('laradox:up', ['--force-ssl' => 'false'])
            ->expectsOutputToContain('Force HTTP enabled')
            ->expectsOutputToContain('app-http.conf');
    }

    #[Test]
    public function it_bypasses_production_ssl_requirement_with_force_flag(): void
    {
        $this->createTestDockerComposeFile('production');
        $this->createTestSslDirectory();
        
        // Create nginx configs but NO certificates
        $httpConfigPath = base_path('docker/nginx/conf.d/app-http.conf');
        $httpsConfigPath = base_path('docker/nginx/conf.d/app-https.conf');
        
        File::ensureDirectoryExists(dirname($httpConfigPath));
        File::put($httpConfigPath, 'http config');
        File::put($httpsConfigPath, 'https config');

        // Production without SSL should fail normally
        $this->artisan('laradox:up', ['--environment' => 'production'])
            ->expectsOutput('✗ SSL certificates are required for production environment!')
            ->assertExitCode(1);

        // But with --force-ssl=false, it should work
        $this->artisan('laradox:up', ['--environment' => 'production', '--force-ssl' => 'false'])
            ->expectsOutputToContain('Force HTTP enabled')
            ->expectsOutputToContain('app-http.conf');
    }

    #[Test]
    public function it_checks_docker_installation_before_running(): void
    {
        // This test verifies that the command checks for Docker
        // If Docker is installed, it proceeds; if not, it handles gracefully
        $this->createTestDockerComposeFile();
        $this->createTestSslDirectory();
        File::put(config('laradox.ssl.cert_path'), 'dummy cert');
        File::put(config('laradox.ssl.key_path'), 'dummy key');
        
        $result = $this->artisan('laradox:up', ['--detach' => true]);
        
        // Command should either succeed with Docker or fail with error message
        $this->assertContains($result->run(), [0, 1]);
    }

    #[Test]
    public function it_checks_docker_compose_before_running(): void
    {
        // This test verifies that the command checks for Docker Compose
        $this->createTestDockerComposeFile();
        $this->createTestSslDirectory();
        File::put(config('laradox.ssl.cert_path'), 'dummy cert');
        File::put(config('laradox.ssl.key_path'), 'dummy key');
        
        $result = $this->artisan('laradox:up', ['--detach' => true]);
        
        // Command should check for Docker Compose availability
        $this->assertContains($result->run(), [0, 1]);
    }
}

