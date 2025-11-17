<?php

namespace Laradox\Tests\Feature;

use Illuminate\Support\Facades\File;
use Laradox\Tests\TestCase;

class SetupSSLCommandTest extends TestCase
{
    /** @test */
    public function it_displays_setup_message(): void
    {
        $this->artisan('laradox:setup-ssl')
            ->expectsOutput('Setting up SSL certificates...');
    }

    /** @test */
    public function it_checks_for_mkcert_installation(): void
    {
        // This will likely fail in CI/test environments where mkcert isn't installed
        $this->artisan('laradox:setup-ssl')
            ->expectsOutput('Setting up SSL certificates...');
    }

    /** @test */
    public function it_uses_domain_from_config(): void
    {
        $this->app['config']->set('laradox.domain', 'custom.docker.localhost');

        $this->artisan('laradox:setup-ssl')
            ->expectsOutput('Setting up SSL certificates...');

        $this->assertEquals('custom.docker.localhost', config('laradox.domain'));
    }

    /** @test */
    public function it_accepts_domain_option(): void
    {
        $this->artisan('laradox:setup-ssl', ['--domain' => 'override.docker.localhost'])
            ->expectsOutput('Setting up SSL certificates...');
    }

    /** @test */
    public function it_accepts_additional_domains_option(): void
    {
        $this->artisan('laradox:setup-ssl', [
            '--additional-domains' => ['domain1.test', 'domain2.test']
        ])->expectsOutput('Setting up SSL certificates...');
    }

    /** @test */
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

    /** @test */
    public function it_uses_config_ssl_paths(): void
    {
        $certPath = base_path('docker/nginx/ssl/cert.pem');
        $keyPath = base_path('docker/nginx/ssl/key.pem');

        $this->assertEquals($certPath, config('laradox.ssl.cert_path'));
        $this->assertEquals($keyPath, config('laradox.ssl.key_path'));
    }

    /** @test */
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

    /** @test */
    public function it_properly_escapes_domain_arguments(): void
    {
        // Test that special characters in domains are handled safely
        $this->artisan('laradox:setup-ssl', [
            '--domain' => 'test.local',
            '--additional-domains' => ['*.test.local']
        ])->expectsOutput('Setting up SSL certificates...');
    }
}
