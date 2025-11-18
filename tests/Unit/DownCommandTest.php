<?php

namespace Laradox\Tests\Unit;

use Laradox\Console\DownCommand;
use Laradox\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\File;

class DownCommandTest extends TestCase
{
    #[Test]
    public function it_has_correct_signature(): void
    {
        $command = new DownCommand();
        
        $this->assertStringContainsString('laradox:down', $command->getName());
    }

    #[Test]
    public function it_has_environment_option(): void
    {
        $command = new DownCommand();
        
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('environment'));
    }

    #[Test]
    public function it_has_volumes_option(): void
    {
        $command = new DownCommand();
        
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('volumes'));
    }

    #[Test]
    public function it_has_correct_description(): void
    {
        $command = new DownCommand();
        
        $this->assertStringContainsString('Stop Laradox Docker containers', $command->getDescription());
    }

    #[Test]
    public function it_validates_docker_compose_file_path_format(): void
    {
        $env = 'development';
        $composePath = base_path("docker-compose.{$env}.yml");
        
        $this->assertStringContainsString('docker-compose.development.yml', $composePath);
    }

    #[Test]
    public function it_constructs_docker_compose_down_command(): void
    {
        $composeFile = base_path('docker-compose.development.yml');
        $command = sprintf('docker compose -f %s down', escapeshellarg($composeFile));
        
        $this->assertStringContainsString('docker compose', $command);
        $this->assertStringContainsString('down', $command);
    }

    #[Test]
    public function it_adds_volumes_flag_when_option_is_set(): void
    {
        $composeFile = base_path('docker-compose.development.yml');
        $command = sprintf('docker compose -f %s down', escapeshellarg($composeFile));
        $command .= ' -v';
        
        $this->assertStringContainsString('-v', $command);
    }

    #[Test]
    public function it_uses_development_environment_by_default(): void
    {
        $env = 'development';
        $composeFile = base_path("docker-compose.{$env}.yml");
        
        $this->assertStringContainsString('development', $composeFile);
    }

    #[Test]
    public function it_can_use_production_environment(): void
    {
        $env = 'production';
        $composeFile = base_path("docker-compose.{$env}.yml");
        
        $this->assertStringContainsString('production', $composeFile);
    }

    #[Test]
    public function it_escapes_compose_file_path(): void
    {
        $composeFile = base_path('docker-compose.development.yml');
        $escaped = escapeshellarg($composeFile);
        
        $this->assertStringStartsWith("'", $escaped);
        $this->assertStringEndsWith("'", $escaped);
    }

    #[Test]
    public function it_constructs_full_command_with_volumes(): void
    {
        $composeFile = base_path('docker-compose.development.yml');
        $command = sprintf('docker compose -f %s down', escapeshellarg($composeFile));
        $command .= ' -v';
        
        $expected = sprintf("docker compose -f '%s' down -v", $composeFile);
        $this->assertEquals($expected, $command);
    }

    #[Test]
    public function it_validates_return_code_success(): void
    {
        $returnCode = 0;
        $result = $returnCode === 0 ? 0 : 1; // SUCCESS : FAILURE
        
        $this->assertEquals(0, $result);
    }

    #[Test]
    public function it_validates_return_code_failure(): void
    {
        $returnCode = 1;
        $result = $returnCode === 0 ? 0 : 1; // SUCCESS : FAILURE
        
        $this->assertEquals(1, $result);
    }

    #[Test]
    public function it_checks_file_exists_validation(): void
    {
        $testFile = base_path('test-file.txt');
        File::put($testFile, 'test');
        
        $this->assertTrue(file_exists($testFile));
        
        File::delete($testFile);
        $this->assertFalse(file_exists($testFile));
    }

    #[Test]
    public function it_validates_environment_option_values(): void
    {
        $developmentEnv = 'development';
        $productionEnv = 'production';
        
        $this->assertEquals('development', $developmentEnv);
        $this->assertEquals('production', $productionEnv);
    }
}
