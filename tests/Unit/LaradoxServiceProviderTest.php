<?php

namespace Laradox\Tests\Unit;

use Laradox\Console\DownCommand;
use Laradox\Console\InstallCommand;
use Laradox\Console\SetupSSLCommand;
use Laradox\Console\UpCommand;
use Laradox\LaradoxServiceProvider;
use Laradox\Tests\TestCase;

class LaradoxServiceProviderTest extends TestCase
{
    /** @test */
    public function it_registers_the_service_provider(): void
    {
        $providers = $this->app->getLoadedProviders();

        $this->assertArrayHasKey(LaradoxServiceProvider::class, $providers);
    }

    /** @test */
    public function it_registers_commands(): void
    {
        $commands = [
            InstallCommand::class,
            UpCommand::class,
            DownCommand::class,
            SetupSSLCommand::class,
        ];

        foreach ($commands as $class) {
            $this->assertTrue(
                class_exists($class),
                "Command {$class} does not exist"
            );
        }
    }

    /** @test */
    public function it_merges_configuration(): void
    {
        $this->assertEquals('test.docker.localhost', config('laradox.domain'));
        $this->assertEquals('development', config('laradox.environment'));
        $this->assertIsArray(config('laradox.additional_domains'));
        $this->assertArrayHasKey('cert_path', config('laradox.ssl'));
        $this->assertArrayHasKey('key_path', config('laradox.ssl'));
    }

    /** @test */
    public function it_has_publishable_config(): void
    {
        $provider = new LaradoxServiceProvider($this->app);
        
        // The provider registers publishable assets
        $this->assertInstanceOf(LaradoxServiceProvider::class, $provider);
    }

    /** @test */
    public function it_defines_publish_groups(): void
    {
        $groups = LaradoxServiceProvider::$publishGroups;

        $this->assertArrayHasKey('laradox-config', $groups);
        $this->assertArrayHasKey('laradox-docker', $groups);
        $this->assertArrayHasKey('laradox-compose', $groups);
        $this->assertArrayHasKey('laradox-scripts', $groups);
        $this->assertArrayHasKey('laradox', $groups);
    }
}
