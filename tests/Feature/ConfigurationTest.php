<?php

namespace Laradox\Tests\Feature;

use Laradox\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class ConfigurationTest extends FeatureTestCase
{
    #[Test]
    public function it_has_default_domain_configuration(): void
    {
        $this->assertEquals('test.docker.localhost', config('laradox.domain'));
    }

    #[Test]
    public function it_has_additional_domains_configuration(): void
    {
        $additionalDomains = config('laradox.additional_domains');

        $this->assertIsArray($additionalDomains);
        $this->assertContains('*.docker.localhost', $additionalDomains);
        $this->assertContains('docker.localhost', $additionalDomains);
    }

    #[Test]
    public function it_has_environment_configuration(): void
    {
        $this->assertEquals('development', config('laradox.environment'));
    }

    #[Test]
    public function it_has_ports_configuration(): void
    {
        $ports = config('laradox.ports');

        $this->assertIsArray($ports);
        $this->assertArrayHasKey('http', $ports);
        $this->assertArrayHasKey('https', $ports);
        $this->assertArrayHasKey('frankenphp', $ports);
    }

    #[Test]
    public function it_has_default_port_values(): void
    {
        $this->assertEquals(80, config('laradox.ports.http'));
        $this->assertEquals(443, config('laradox.ports.https'));
        $this->assertEquals(8080, config('laradox.ports.frankenphp'));
    }

    #[Test]
    public function it_has_queue_workers_configuration(): void
    {
        $this->assertIsInt(config('laradox.queue_workers'));
    }

    #[Test]
    public function it_has_php_configuration(): void
    {
        $phpConfig = config('laradox.php');

        $this->assertIsArray($phpConfig);
        $this->assertArrayHasKey('version', $phpConfig);
        $this->assertArrayHasKey('user_id', $phpConfig);
        $this->assertArrayHasKey('group_id', $phpConfig);
    }

    #[Test]
    public function it_has_ssl_configuration(): void
    {
        $sslConfig = config('laradox.ssl');

        $this->assertIsArray($sslConfig);
        $this->assertArrayHasKey('cert_path', $sslConfig);
        $this->assertArrayHasKey('key_path', $sslConfig);
    }

    #[Test]
    public function it_has_ssl_paths_pointing_to_correct_location(): void
    {
        $certPath = config('laradox.ssl.cert_path');
        $keyPath = config('laradox.ssl.key_path');

        $this->assertStringContainsString('docker/nginx/ssl/cert.pem', $certPath);
        $this->assertStringContainsString('docker/nginx/ssl/key.pem', $keyPath);
    }

    #[Test]
    public function it_has_auto_install_configuration(): void
    {
        $this->assertIsBool(config('laradox.auto_install'));
    }
}
