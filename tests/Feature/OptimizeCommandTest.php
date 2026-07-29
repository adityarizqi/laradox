<?php

namespace Laradox\Tests\Feature;

use Illuminate\Support\Facades\File;
use Laradox\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class OptimizeCommandTest extends FeatureTestCase
{
    #[Test]
    public function it_fails_when_docker_compose_file_not_found(): void
    {
        $this->artisan('laradox:optimize')
            ->expectsOutput('✗ Docker Compose file not found: docker-compose.production.yml')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_defaults_to_the_production_compose_file(): void
    {
        $this->createTestDockerComposeFile('production');

        $this->artisan('laradox:optimize')
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);

        $this->assertTrue(File::exists(base_path('docker-compose.production.yml')));
    }

    #[Test]
    public function it_shows_error_when_no_containers_are_running(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:optimize', ['--environment' => 'development'])
            ->expectsOutput('✗ No containers are running!')
            ->expectsOutput('Start containers with: php artisan laradox:up --detach')
            ->assertExitCode(1);
    }

    #[Test]
    public function the_clear_mode_also_requires_running_containers(): void
    {
        $this->createTestDockerComposeFile('production');

        $this->artisan('laradox:optimize', ['--clear' => true])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_a_custom_service(): void
    {
        $this->createTestDockerComposeFile('production');

        $this->artisan('laradox:optimize', ['--service' => 'worker'])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }
}
