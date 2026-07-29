<?php

namespace Laradox\Tests\Feature;

use Illuminate\Support\Facades\File;
use Laradox\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class DeployCommandTest extends FeatureTestCase
{
    #[Test]
    public function it_fails_when_docker_compose_file_not_found(): void
    {
        $this->artisan('laradox:deploy')
            ->expectsOutput('✗ Docker Compose file not found: docker-compose.production.yml')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_rejects_a_non_numeric_timeout(): void
    {
        $this->createTestDockerComposeFile('production');

        $this->artisan('laradox:deploy', ['--timeout' => 'later', '--dry-run' => true])
            ->expectsOutput('The --timeout option must be a positive integer.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_fails_when_the_nginx_config_has_not_been_generated(): void
    {
        $this->createTestDockerComposeFile('production');
        File::ensureDirectoryExists(base_path('docker/nginx/conf.d'));

        $this->artisan('laradox:deploy', ['--dry-run' => true])
            ->expectsOutput('✗ nginx configuration has not been generated yet.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_a_generated_nginx_config(): void
    {
        $this->createTestDockerComposeFile('production');
        File::ensureDirectoryExists(base_path('docker/nginx/conf.d'));
        File::put(base_path('docker/nginx/conf.d/app.conf'), 'server {}');

        $this->artisan('laradox:deploy', ['--dry-run' => true])
            ->expectsOutputToContain('Deployment plan')
            ->assertExitCode(0);
    }

    #[Test]
    public function the_dry_run_prints_the_plan_without_executing_it(): void
    {
        $this->createTestDockerComposeFile('production');

        $this->artisan('laradox:deploy', ['--dry-run' => true])
            ->expectsOutputToContain('Deployment plan')
            ->expectsOutputToContain('Pull latest code')
            ->expectsOutputToContain('Run database migrations')
            ->expectsOutput('Dry run — nothing was executed.')
            ->assertExitCode(0);
    }

    #[Test]
    public function the_dry_run_honours_the_skip_options(): void
    {
        $this->createTestDockerComposeFile('production');

        $this->artisan('laradox:deploy', [
            '--dry-run' => true,
            '--no-pull' => true,
            '--no-migrate' => true,
        ])
            ->doesntExpectOutputToContain('Pull latest code')
            ->doesntExpectOutputToContain('Run database migrations')
            ->expectsOutputToContain('Start containers')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_refuses_to_deploy_non_interactively_without_force(): void
    {
        $this->createTestDockerComposeFile('production');

        $this->artisan('laradox:deploy', ['--no-interaction' => true])
            ->expectsOutput('✗ Deployment requires confirmation.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_the_development_environment(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:deploy', ['--environment' => 'development', '--dry-run' => true])
            ->expectsOutputToContain('docker-compose.development.yml (development)')
            ->assertExitCode(0);
    }
}
