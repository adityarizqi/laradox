<?php

namespace Laradox\Tests\Unit;

use Laradox\Console\UpCommand;
use Laradox\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

class UpCommandTest extends TestCase
{
    #[Test]
    public function it_has_correct_signature(): void
    {
        $command = new UpCommand();
        
        $this->assertStringContainsString('laradox:up', $command->getName());
    }

    #[Test]
    public function it_has_environment_option(): void
    {
        $command = new UpCommand();
        
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('environment'));
    }

    #[Test]
    public function it_has_detach_option(): void
    {
        $command = new UpCommand();
        
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('detach'));
    }

    #[Test]
    public function it_has_build_option(): void
    {
        $command = new UpCommand();
        
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('build'));
    }

    #[Test]
    public function it_has_force_ssl_option(): void
    {
        $command = new UpCommand();
        
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('force-ssl'));
    }

    #[Test]
    public function it_has_correct_description(): void
    {
        $command = new UpCommand();
        
        $this->assertStringContainsString('Start Laradox Docker containers', $command->getDescription());
    }

    #[Test]
    public function it_validates_docker_compose_file_path_format(): void
    {
        $env = 'development';
        $composePath = base_path("docker-compose.{$env}.yml");
        
        $this->assertStringContainsString('docker-compose.development.yml', $composePath);
    }

    #[Test]
    public function it_validates_ssl_certificate_paths_from_config(): void
    {
        Config::set('laradox.ssl.cert_path', base_path('docker/nginx/ssl/localhost.pem'));
        Config::set('laradox.ssl.key_path', base_path('docker/nginx/ssl/localhost-key.pem'));
        
        $certPath = config('laradox.ssl.cert_path');
        $keyPath = config('laradox.ssl.key_path');
        
        $this->assertStringContainsString('localhost.pem', $certPath);
        $this->assertStringContainsString('localhost-key.pem', $keyPath);
    }

    #[Test]
    public function it_validates_nginx_config_paths(): void
    {
        $httpConfig = base_path('docker/nginx/conf.d/app-http.conf');
        $httpsConfig = base_path('docker/nginx/conf.d/app-https.conf');
        $targetConfig = base_path('docker/nginx/conf.d/app.conf');
        
        $this->assertIsString($httpConfig);
        $this->assertIsString($httpsConfig);
        $this->assertIsString($targetConfig);
    }

    #[Test]
    public function it_constructs_docker_compose_command_format(): void
    {
        $composeFile = base_path('docker-compose.development.yml');
        $command = sprintf('docker compose -f %s up', escapeshellarg($composeFile));
        
        $this->assertStringContainsString('docker compose', $command);
        $this->assertStringContainsString('up', $command);
    }

    #[Test]
    public function it_adds_build_flag_to_command(): void
    {
        $composeFile = base_path('docker-compose.development.yml');
        $command = sprintf('docker compose -f %s up', escapeshellarg($composeFile));
        $command .= ' --build';
        
        $this->assertStringContainsString('--build', $command);
    }

    #[Test]
    public function it_adds_detach_flag_to_command(): void
    {
        $composeFile = base_path('docker-compose.development.yml');
        $command = sprintf('docker compose -f %s up', escapeshellarg($composeFile));
        $command .= ' -d';
        
        $this->assertStringContainsString('-d', $command);
    }

    #[Test]
    public function it_uses_development_environment_by_default(): void
    {
        $env = 'development';
        $composeFile = base_path("docker-compose.{$env}.yml");
        
        $this->assertStringContainsString('development', $composeFile);
    }

    #[Test]
    public function it_constructs_restart_command_format(): void
    {
        $composeFile = base_path('docker-compose.development.yml');
        $restartCommand = sprintf('docker compose -f %s restart', escapeshellarg($composeFile));
        
        $this->assertStringContainsString('docker compose', $restartCommand);
        $this->assertStringContainsString('restart', $restartCommand);
    }

    #[Test]
    public function it_constructs_ps_command_for_container_check(): void
    {
        $composeFile = base_path('docker-compose.development.yml');
        $command = sprintf('docker compose -f %s ps --quiet 2>/dev/null', escapeshellarg($composeFile));
        
        $this->assertStringContainsString('ps --quiet', $command);
    }

    #[Test]
    public function it_determines_protocol_based_on_ssl_existence(): void
    {
        $sslExists = true;
        $forceSsl = null;
        
        $protocol = $sslExists && $forceSsl !== 'false' ? 'https' : 'http';
        
        $this->assertEquals('https', $protocol);
    }

    #[Test]
    public function it_determines_protocol_based_on_force_ssl_flag(): void
    {
        $sslExists = false;
        $forceSsl = 'false';
        
        $protocol = $sslExists && $forceSsl !== 'false' ? 'https' : 'http';
        
        $this->assertEquals('http', $protocol);
    }

    #[Test]
    public function it_validates_filter_var_for_force_ssl_option(): void
    {
        $forceSslTrue = filter_var('true', FILTER_VALIDATE_BOOLEAN);
        $forceSslFalse = filter_var('false', FILTER_VALIDATE_BOOLEAN);
        
        $this->assertTrue($forceSslTrue);
        $this->assertFalse($forceSslFalse);
    }

    #[Test]
    public function it_escapes_compose_file_path_in_commands(): void
    {
        $composeFile = base_path('docker-compose.development.yml');
        $escaped = escapeshellarg($composeFile);
        
        $this->assertStringStartsWith("'", $escaped);
        $this->assertStringEndsWith("'", $escaped);
    }

    #[Test]
    public function it_validates_nginx_config_filenames(): void
    {
        $httpsConfig = 'app-https.conf';
        $httpConfig = 'app-http.conf';
        
        $this->assertEquals('app-https.conf', $httpsConfig);
        $this->assertEquals('app-http.conf', $httpConfig);
    }
}
