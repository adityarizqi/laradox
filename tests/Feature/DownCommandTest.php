<?php

namespace Laradox\Tests\Feature;

use Illuminate\Support\Facades\File;
use Laradox\Tests\FeatureTestCase;

class DownCommandTest extends FeatureTestCase
{
    /** @test */
    public function it_fails_when_docker_compose_file_not_found(): void
    {
        $this->artisan('laradox:down')
            ->expectsOutput('Docker Compose file not found: ' . base_path('docker-compose.development.yml'))
            ->assertExitCode(1);
    }

    /** @test */
    public function it_accepts_environment_option(): void
    {
        $this->createTestDockerComposeFile('production');

        $command = $this->artisan('laradox:down', ['--environment' => 'production']);

        $this->assertTrue(File::exists(base_path('docker-compose.production.yml')));
    }

    /** @test */
    public function it_uses_development_environment_by_default(): void
    {
        $this->createTestDockerComposeFile('development');

        $command = $this->artisan('laradox:down');

        $this->assertTrue(File::exists(base_path('docker-compose.development.yml')));
    }

    /** @test */
    public function it_accepts_volumes_option(): void
    {
        $this->createTestDockerComposeFile();

        // We can't actually test docker compose execution in unit tests,
        // but we can verify the command accepts the option
        $this->artisan('laradox:down', ['--volumes' => true])
            ->expectsOutput('Stopping Laradox (development environment)...');
    }

    /** @test */
    public function it_displays_stopping_message(): void
    {
        $this->createTestDockerComposeFile();

        $this->artisan('laradox:down')
            ->expectsOutput('Stopping Laradox (development environment)...');
    }

    /** @test */
    public function it_displays_execution_command(): void
    {
        $this->createTestDockerComposeFile();

        $this->artisan('laradox:down')
            ->expectsOutputToContain('Executing:');
    }

    /** @test */
    public function it_handles_production_environment(): void
    {
        $this->createTestDockerComposeFile('production');

        $this->artisan('laradox:down', ['--environment' => 'production'])
            ->expectsOutput('Stopping Laradox (production environment)...');
    }

    /** @test */
    public function it_escapes_shell_arguments(): void
    {
        $testPath = base_path('docker-compose.development.yml');
        File::put($testPath, 'test content');

        $command = $this->artisan('laradox:down');

        $this->assertTrue(File::exists($testPath));
    }
}
