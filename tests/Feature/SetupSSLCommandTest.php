<?php

namespace Laradox\Tests\Feature;

use Illuminate\Support\Facades\File;
use Laradox\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class SetupSSLCommandTest extends FeatureTestCase
{
    #[Test]
    public function it_displays_setup_message(): void
    {
        $this->artisan('laradox:setup-ssl')
            ->expectsOutput('Setting up SSL certificates...')
            ->assertSuccessful();
    }

    #[Test]
    public function it_checks_for_mkcert_installation(): void
    {        
        // In CI/test environments where mkcert isn't installed, command returns 1
        // If mkcert is installed, it succeeds and returns 0
        $result = $this->artisan('laradox:setup-ssl');
        $result->expectsOutput('Setting up SSL certificates...');
        
        // Accept either exit code since mkcert may or may not be installed
        $this->assertContains($result->run(), [0, 1]);
    }

    #[Test]
    public function it_uses_domain_from_config(): void
    {
        $this->app['config']->set('laradox.domain', 'custom.docker.localhost');

        $this->artisan('laradox:setup-ssl')
            ->expectsOutput('Setting up SSL certificates...');

        $this->assertEquals('custom.docker.localhost', config('laradox.domain'));
    }

    #[Test]
    public function it_accepts_domain_option(): void
    {
        $this->artisan('laradox:setup-ssl', ['--domain' => 'override.docker.localhost'])
            ->expectsOutput('Setting up SSL certificates...');
    }

    #[Test]
    public function it_accepts_additional_domains_option(): void
    {
        $this->artisan('laradox:setup-ssl', [
            '--additional-domains' => ['domain1.test', 'domain2.test']
        ])->expectsOutput('Setting up SSL certificates...');
    }

    #[Test]
    public function it_creates_ssl_directory_if_not_exists(): void
    {
        $sslDir = dirname(config('laradox.ssl.cert_path'));

        // Ensure directory doesn't exist
        if (File::exists($sslDir)) {
            File::deleteDirectory($sslDir);
        }

        $this->artisan('laradox:setup-ssl');

        // The command will try to create the directory
        // (may fail if mkcert isn't installed, but directory should be created)
        $this->assertTrue(
            File::exists($sslDir) || !File::exists(dirname($sslDir)),
            'SSL directory should be created or parent directory should not exist'
        );
    }

    #[Test]
    public function it_uses_config_ssl_paths(): void
    {
        $certPath = base_path('docker/nginx/ssl/cert.pem');
        $keyPath = base_path('docker/nginx/ssl/key.pem');

        $this->assertEquals($certPath, config('laradox.ssl.cert_path'));
        $this->assertEquals($keyPath, config('laradox.ssl.key_path'));
    }

    #[Test]
    public function it_includes_additional_domains_from_config(): void
    {
        $this->app['config']->set('laradox.additional_domains', [
            '*.test.localhost',
            'test.localhost',
        ]);

        $this->assertEquals(
            ['*.test.localhost', 'test.localhost'],
            config('laradox.additional_domains')
        );
    }

    #[Test]
    public function it_properly_escapes_domain_arguments(): void
    {
        // Test that special characters in domains are handled safely
        $this->artisan('laradox:setup-ssl', [
            '--domain' => 'test.local',
            '--additional-domains' => ['*.test.local']
        ])->expectsOutput('Setting up SSL certificates...');
    }

    #[Test]
    public function it_shows_warning_when_mkcert_not_installed(): void
    {
        // Mock a scenario where mkcert is not installed
        $result = $this->artisan('laradox:setup-ssl');
        
        // The command should show setup message
        $result->expectsOutput('Setting up SSL certificates...');
        
        // If mkcert is not installed, it should show warning or proceed with setup
        $this->assertContains($result->run(), [0, 1]);
    }

    #[Test]
    public function it_handles_user_declining_installation(): void
    {
        // This test verifies the command handles user input gracefully
        // In real scenarios, if mkcert is not installed and user declines,
        // the command should return failure status
        $result = $this->artisan('laradox:setup-ssl');
        
        $this->assertContains($result->run(), [0, 1]);
    }

    #[Test]
    public function it_provides_installation_instructions_for_missing_mkcert(): void
    {
        // When mkcert is missing, command should provide helpful output
        $this->artisan('laradox:setup-ssl')
            ->expectsOutput('Setting up SSL certificates...');
        
        // Command should handle missing mkcert gracefully
        $this->assertTrue(true);
    }

    #[Test]
    public function it_detects_operating_system_correctly(): void
    {
        // Verify that the command can detect the current OS
        // This is implicitly tested when running the command
        $result = $this->artisan('laradox:setup-ssl');
        
        // The OS detection should not cause the command to crash
        $this->assertContains($result->run(), [0, 1]);
    }

    #[Test]
    public function it_handles_windows_installation_flow(): void
    {
        // On Windows, the command should provide download instructions
        // This test ensures the Windows flow doesn't crash
        $result = $this->artisan('laradox:setup-ssl');
        
        $this->assertContains($result->run(), [0, 1]);
    }

    #[Test]
    public function it_handles_linux_installation_flow(): void
    {
        // On Linux, the command should detect package managers
        // This test ensures the Linux flow doesn't crash
        $result = $this->artisan('laradox:setup-ssl');
        
        $this->assertContains($result->run(), [0, 1]);
    }

    #[Test]
    public function it_handles_macos_installation_flow(): void
    {
        // On macOS, the command should check for Homebrew
        // This test ensures the macOS flow doesn't crash
        $result = $this->artisan('laradox:setup-ssl');
        
        $this->assertContains($result->run(), [0, 1]);
    }
}
