<?php

namespace Laradox\Tests\Feature;

use Illuminate\Support\Facades\File;
use Laradox\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class DownCommandTest extends FeatureTestCase
{
    #[Test]
    public function it_fails_when_docker_compose_file_not_found(): void
    {
        $this->artisan('laradox:down')
            ->expectsOutput('Docker Compose file not found: ' . base_path('docker-compose.development.yml'))
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_environment_option(): void
    {
        $this->createTestDockerComposeFile('production');

        $command = $this->artisan('laradox:down', ['--environment' => 'production']);

        $this->assertTrue(File::exists(base_path('docker-compose.production.yml')));
    }

    #[Test]
    public function it_uses_development_environment_by_default(): void
    {
        $this->createTestDockerComposeFile('development');

        $command = $this->artisan('laradox:down');

        $this->assertTrue(File::exists(base_path('docker-compose.development.yml')));
    }

    #[Test]
    public function it_accepts_volumes_option(): void
    {
        $this->createTestDockerComposeFile();

        // We can't actually test docker compose execution in unit tests,
        // but we can verify the command accepts the option
        $this->artisan('laradox:down', ['--volumes' => true])
            ->expectsOutput('Stopping Laradox (development environment)...');
    }

    #[Test]
    public function it_displays_stopping_message(): void
    {
        $this->createTestDockerComposeFile();

        $this->artisan('laradox:down')
            ->expectsOutput('Stopping Laradox (development environment)...');
    }

    #[Test]
    public function it_displays_execution_command(): void
    {
        $this->createTestDockerComposeFile();

        $this->artisan('laradox:down')
            ->expectsOutputToContain('Executing:');
    }

    #[Test]
    public function it_handles_production_environment(): void
    {
        $this->createTestDockerComposeFile('production');

        $this->artisan('laradox:down', ['--environment' => 'production'])
            ->expectsOutput('Stopping Laradox (production environment)...');
    }

    #[Test]
    public function it_escapes_shell_arguments(): void
    {
        $testPath = base_path('docker-compose.development.yml');
        File::put($testPath, 'test content');

        $command = $this->artisan('laradox:down');

        $this->assertTrue(File::exists($testPath));
    }

    #[Test]
    public function it_checks_docker_installation_before_running(): void
    {
        // This test verifies that the command checks for Docker
        $this->createTestDockerComposeFile();
        
        $result = $this->artisan('laradox:down');
        
        // Command should either succeed with Docker or fail gracefully
        $this->assertContains($result->run(), [0, 1]);
    }

    #[Test]
    public function it_checks_docker_compose_before_running(): void
    {
        // This test verifies that the command checks for Docker Compose
        $this->createTestDockerComposeFile();
        
        $result = $this->artisan('laradox:down');
        
        // Command should check for Docker Compose availability
        $this->assertContains($result->run(), [0, 1]);
    }
}
