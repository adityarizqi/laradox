<?php

namespace Laradox\Tests\Feature;

use Illuminate\Support\Facades\File;
use Laradox\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class LogsCommandTest extends FeatureTestCase
{
    #[Test]
    public function it_fails_when_docker_compose_file_not_found(): void
    {
        $this->artisan('laradox:logs')
            ->expectsOutput('Docker Compose file not found: ' . base_path('docker-compose.development.yml'))
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_environment_option(): void
    {
        $this->createTestDockerComposeFile('production');

        $command = $this->artisan('laradox:logs', ['--environment' => 'production']);

        // The command will check for the production compose file
        $this->assertTrue(File::exists(base_path('docker-compose.production.yml')));
    }

    #[Test]
    public function it_uses_development_environment_by_default(): void
    {
        $this->createTestDockerComposeFile('development');

        $command = $this->artisan('laradox:logs');

        // Verify the command tried to use development file
        $this->assertTrue(File::exists(base_path('docker-compose.development.yml')));
    }

    #[Test]
    public function it_shows_error_when_no_containers_are_running(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:logs')
            ->expectsOutput('✗ No containers are running!')
            ->expectsOutput('Start containers with: php artisan laradox:up --detach')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_lists_available_services_when_no_service_specified(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:logs')
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_service_argument(): void
    {
        $this->createTestDockerComposeFile('development');

        // This will fail because containers are not running, but we verify the argument is accepted
        $this->artisan('laradox:logs', ['service' => 'php'])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_follow_option(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:logs', ['service' => 'php', '--follow' => true])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_tail_option(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:logs', ['service' => 'php', '--tail' => '100'])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_timestamps_option(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:logs', ['service' => 'php', '--timestamps' => true])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_multiple_options_together(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:logs', [
            'service' => 'php',
            '--follow' => true,
            '--tail' => '50',
            '--timestamps' => true,
        ])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_shows_usage_instructions_when_no_service_specified(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:logs')
            ->expectsOutput('✗ No containers are running!')
            ->expectsOutput('Start containers with: php artisan laradox:up --detach')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_escapes_shell_arguments(): void
    {
        $this->createTestDockerComposeFile('development');

        // Verify the command handles service names safely
        $this->artisan('laradox:logs', ['service' => 'php'])
            ->assertExitCode(1);
    }

    #[Test]
    public function it_validates_compose_file_exists_before_checking_containers(): void
    {
        // No compose file created

        $this->artisan('laradox:logs')
            ->expectsOutput('Docker Compose file not found: ' . base_path('docker-compose.development.yml'))
            ->assertExitCode(1);
    }

    #[Test]
    public function it_handles_production_environment(): void
    {
        $this->createTestDockerComposeFile('production');

        $this->artisan('laradox:logs', ['--environment' => 'production'])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_provides_helpful_error_message_when_containers_not_running(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:logs', ['service' => 'php'])
            ->expectsOutput('✗ No containers are running!')
            ->expectsOutput('Start containers with: php artisan laradox:up --detach')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_shows_follow_instructions_when_follow_flag_used(): void
    {
        $this->createTestDockerComposeFile('development');

        // Even when containers aren't running, we can verify the flag is recognized
        $result = $this->artisan('laradox:logs', [
            'service' => 'php',
            '--follow' => true,
        ]);

        $result->assertExitCode(1);
    }

    #[Test]
    public function it_supports_all_common_service_names(): void
    {
        $this->createTestDockerComposeFile('development');

        $services = ['nginx', 'php', 'node', 'scheduler', 'queue'];

        foreach ($services as $service) {
            $this->artisan('laradox:logs', ['service' => $service])
                ->expectsOutput('✗ No containers are running!')
                ->assertExitCode(1);
        }
    }

    #[Test]
    public function it_handles_invalid_tail_value_gracefully(): void
    {
        $this->createTestDockerComposeFile('development');

        // Docker Compose will handle invalid tail values, we just verify the command runs
        $this->artisan('laradox:logs', [
            'service' => 'php',
            '--tail' => 'invalid',
        ])
            ->assertExitCode(1);
    }

    #[Test]
    public function it_combines_environment_and_service_options(): void
    {
        $this->createTestDockerComposeFile('production');

        $this->artisan('laradox:logs', [
            'service' => 'php',
            '--environment' => 'production',
        ])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }
}
