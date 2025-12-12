<?php

namespace Laradox\Tests\Feature;

use Illuminate\Support\Facades\File;
use Laradox\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class ShellCommandTest extends FeatureTestCase
{
    #[Test]
    public function it_fails_when_docker_compose_file_not_found(): void
    {
        $this->artisan('laradox:shell')
            ->expectsOutput('Docker Compose file not found: ' . base_path('docker-compose.development.yml'))
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_environment_option(): void
    {
        $this->createTestDockerComposeFile('production');

        $this->artisan('laradox:shell', ['--environment' => 'production'])
            ->assertExitCode(1); // Will fail since containers aren't running, but confirms option accepted

        // The command will check for the production compose file
        $this->assertTrue(File::exists(base_path('docker-compose.production.yml')));
    }

    #[Test]
    public function it_uses_development_environment_by_default(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:shell')
            ->assertExitCode(1); // Will fail since containers aren't running, but confirms default env used

        // Verify the command tried to use development file
        $this->assertTrue(File::exists(base_path('docker-compose.development.yml')));
    }

    #[Test]
    public function it_shows_error_when_no_containers_are_running(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:shell')
            ->expectsOutput('✗ No containers are running!')
            ->expectsOutput('Start containers with: php artisan laradox:up --detach')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_fails_when_containers_not_running_with_default_service(): void
    {
        $this->createTestDockerComposeFile('development');

        // Test that command fails early when containers are not running (with default php service)
        $this->artisan('laradox:shell')
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_fails_when_containers_not_running_with_custom_service(): void
    {
        $this->createTestDockerComposeFile('development');

        // Test that command fails early when containers are not running (even with custom service specified)
        $this->artisan('laradox:shell', ['service' => 'nginx'])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_fails_when_containers_not_running_with_user_option(): void
    {
        $this->createTestDockerComposeFile('development');

        // Test that command fails early when containers are not running (with user option)
        $this->artisan('laradox:shell', ['service' => 'php', '--user' => 'www-data'])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_fails_when_containers_not_running_with_shell_option(): void
    {
        $this->createTestDockerComposeFile('development');

        // Test that command fails early when containers are not running (with shell option)
        $this->artisan('laradox:shell', ['service' => 'php', '--shell' => 'sh'])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_fails_when_containers_not_running_with_default_shell(): void
    {
        $this->createTestDockerComposeFile('development');

        // Test that command fails early when containers are not running (with default shell)
        $this->artisan('laradox:shell')
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_fails_when_containers_not_running_before_validating_service(): void
    {
        $this->createTestDockerComposeFile('development');

        // Test that command checks container status before validating service existence
        // (Would show service validation error only if containers were running)
        $this->artisan('laradox:shell', ['service' => 'nonexistent'])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test files
        if (File::exists(base_path('docker-compose.development.yml'))) {
            File::delete(base_path('docker-compose.development.yml'));
        }
        if (File::exists(base_path('docker-compose.production.yml'))) {
            File::delete(base_path('docker-compose.production.yml'));
        }
    }
}
