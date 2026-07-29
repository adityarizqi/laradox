<?php

namespace Laradox\Tests\Feature;

use Illuminate\Support\Facades\File;
use Laradox\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class StatusCommandTest extends FeatureTestCase
{
    #[Test]
    public function it_fails_when_docker_compose_file_not_found(): void
    {
        $this->artisan('laradox:status')
            ->expectsOutput('✗ Docker Compose file not found: docker-compose.development.yml')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_reports_services_that_have_no_container_yet(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:status')
            ->expectsOutputToContain('not created')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_suggests_starting_the_containers_when_nothing_runs(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:status')
            ->expectsOutputToContain('php artisan laradox:up --detach')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_environment_option(): void
    {
        $this->createTestDockerComposeFile('production');

        $this->artisan('laradox:status', ['--environment' => 'production'])
            ->assertExitCode(1);

        $this->assertTrue(File::exists(base_path('docker-compose.production.yml')));
    }

    #[Test]
    public function it_rejects_an_unknown_service(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:status', ['service' => 'does-not-exist'])
            ->expectsOutputToContain("Service 'does-not-exist' is not defined")
            ->assertExitCode(1);
    }

    #[Test]
    public function it_rejects_watch_combined_with_json(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:status', ['--watch' => true, '--json' => true])
            ->expectsOutput('The --watch option cannot be combined with --json.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_rejects_a_non_numeric_watch_interval(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:status', ['--watch' => true, '--interval' => 'soon'])
            ->expectsOutput('The --interval option must be a positive integer.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_rejects_a_zero_watch_interval(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:status', ['--watch' => true, '--interval' => '0'])
            ->expectsOutput('The --interval option must be a positive integer.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_can_report_as_json(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:status', ['--json' => true])
            ->expectsOutputToContain('"services"')
            ->assertExitCode(1);
    }
}
