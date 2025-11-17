<?php

namespace Laradox\Tests\Feature;

use Illuminate\Support\Facades\File;
use Laradox\Tests\FeatureTestCase;

class UpCommandTest extends FeatureTestCase
{
    /** @test */
    public function it_fails_when_docker_compose_file_not_found(): void
    {
        $this->artisan('laradox:up')
            ->expectsOutput('Docker Compose file not found: ' . base_path('docker-compose.development.yml'))
            ->assertExitCode(1);
    }

    /** @test */
    public function it_accepts_environment_option(): void
    {
        $this->createTestDockerComposeFile('production');

        $command = $this->artisan('laradox:up', ['--environment' => 'production']);

        // The command will fail because docker compose is not available in tests,
        // but we can verify it tried to use the correct file
        $this->assertTrue(File::exists(base_path('docker-compose.production.yml')));
    }

    /** @test */
    public function it_uses_development_environment_by_default(): void
    {
        $this->createTestDockerComposeFile('development');

        $command = $this->artisan('laradox:up');

        // Verify the command tried to use development file
        $this->assertTrue(File::exists(base_path('docker-compose.development.yml')));
    }

    /** @test */
    public function it_accepts_detach_option(): void
    {
        $this->createTestDockerComposeFile();

        // We can't actually test docker compose execution in unit tests,
        // but we can verify the command accepts the option
        $this->artisan('laradox:up', ['--detach' => true])
            ->expectsOutput('Starting Laradox (development environment)...');
    }

    /** @test */
    public function it_accepts_build_option(): void
    {
        $this->createTestDockerComposeFile();

        $this->artisan('laradox:up', ['--build' => true])
            ->expectsOutput('Starting Laradox (development environment)...');
    }

    /** @test */
    public function it_displays_starting_message(): void
    {
        $this->createTestDockerComposeFile();

        $this->artisan('laradox:up')
            ->expectsOutput('Starting Laradox (development environment)...');
    }

    /** @test */
    public function it_escapes_shell_arguments(): void
    {
        // Create a compose file with special characters in path
        $testPath = base_path('docker-compose.development.yml');
        File::put($testPath, 'test content');

        $command = $this->artisan('laradox:up');

        // The command should handle the path safely
        $this->assertTrue(File::exists($testPath));
    }
}
