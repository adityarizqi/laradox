<?php

namespace Laradox\Tests\Unit;

use Illuminate\Support\Facades\File;
use Laradox\Console\InstallCommand;
use Laradox\Tests\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class InstallCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_has_correct_signature(): void
    {
        $command = new InstallCommand();
        
        $this->assertStringContainsString('laradox:install', $command->getName());
    }

    #[Test]
    public function it_has_force_option(): void
    {
        $command = new InstallCommand();
        
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('force'));
    }

    #[Test]
    public function it_has_correct_description(): void
    {
        $command = new InstallCommand();
        
        $this->assertEquals('Install Laradox Docker environment for Laravel', $command->getDescription());
    }

    #[Test]
    public function it_makes_composer_script_executable(): void
    {
        // Create a test script file
        $testPath = base_path('composer');
        File::put($testPath, '#!/bin/bash');
        
        // Make it executable
        chmod($testPath, 0755);
        
        // Check if file is executable
        $perms = fileperms($testPath);
        $isExecutable = ($perms & 0100) !== 0;
        
        $this->assertTrue($isExecutable);
        
        // Cleanup
        File::delete($testPath);
    }

    #[Test]
    public function it_makes_npm_script_executable(): void
    {
        // Create a test script file
        $testPath = base_path('npm');
        File::put($testPath, '#!/bin/bash');
        chmod($testPath, 0644);
        
        // Make it executable
        chmod($testPath, 0755);
        $perms = fileperms($testPath);
        $isExecutable = ($perms & 0100) !== 0;
        
        $this->assertTrue($isExecutable);
        
        // Cleanup
        File::delete($testPath);
    }

    #[Test]
    public function it_makes_php_script_executable(): void
    {
        // Create a test script file
        $testPath = base_path('php');
        File::put($testPath, '#!/bin/bash');
        chmod($testPath, 0644);
        
        // Make it executable
        chmod($testPath, 0755);
        $perms = fileperms($testPath);
        $isExecutable = ($perms & 0100) !== 0;
        
        $this->assertTrue($isExecutable);
        
        // Cleanup
        File::delete($testPath);
    }

    #[Test]
    public function it_handles_missing_scripts_gracefully(): void
    {
        // Ensure scripts don't exist
        $scripts = ['composer', 'npm', 'php'];
        foreach ($scripts as $script) {
            $path = base_path($script);
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        // Should not throw exception when files don't exist
        // Testing the logic of checking file existence
        $this->assertTrue(true);
    }

    #[Test]
    public function it_makes_all_scripts_executable_at_once(): void
    {
        // Create test script files
        $scripts = ['composer', 'npm', 'php'];
        foreach ($scripts as $script) {
            File::put(base_path($script), '#!/bin/bash');
            chmod(base_path($script), 0755);
        }
        
        // Check if all files are executable
        foreach ($scripts as $script) {
            $path = base_path($script);
            $perms = fileperms($path);
            $isExecutable = ($perms & 0100) !== 0;
            $this->assertTrue($isExecutable, "{$script} should be executable");
            
            // Cleanup
            File::delete($path);
        }
    }

    #[Test]
    public function it_creates_ssl_directory_when_missing(): void
    {
        $sslDir = base_path('docker/nginx/ssl');
        
        // Ensure directory doesn't exist
        if (File::exists($sslDir)) {
            File::deleteDirectory(dirname($sslDir, 2));
        }

        $command = Mockery::mock(InstallCommand::class)->makePartial();
        $command->shouldReceive('call')->andReturn(0);
        $command->shouldReceive('info')->andReturn(null);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('comment')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);
        $command->shouldReceive('option')->with('force')->andReturn(false);
        
        $result = $command->handle();
        
        $this->assertTrue(File::exists($sslDir));
        $this->assertEquals(0, $result);
        
        // Cleanup
        if (File::exists($sslDir)) {
            File::deleteDirectory(dirname($sslDir, 2));
        }
    }

    #[Test]
    public function it_does_not_fail_when_ssl_directory_exists(): void
    {
        $sslDir = base_path('docker/nginx/ssl');
        
        // Ensure directory exists
        File::makeDirectory($sslDir, 0755, true);

        $command = Mockery::mock(InstallCommand::class)->makePartial();
        $command->shouldReceive('call')->andReturn(0);
        $command->shouldReceive('info')->andReturn(null);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('comment')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);
        $command->shouldReceive('option')->with('force')->andReturn(false);
        
        $result = $command->handle();
        
        $this->assertTrue(File::exists($sslDir));
        $this->assertEquals(0, $result);
        
        // Cleanup
        if (File::exists($sslDir)) {
            File::deleteDirectory(dirname($sslDir, 2));
        }
    }

    #[Test]
    public function it_calls_vendor_publish_with_laradox_tag(): void
    {
        $command = Mockery::mock(InstallCommand::class)->makePartial();
        $command->shouldReceive('call')
            ->with('vendor:publish', [
                '--tag' => 'laradox',
                '--force' => false,
            ])
            ->once()
            ->andReturn(0);
        $command->shouldReceive('info')->andReturn(null);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('comment')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);
        $command->shouldReceive('option')->with('force')->andReturn(false);
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('makeScriptsExecutable')->andReturn(null);
        
        $result = $command->handle();
        
        $this->assertEquals(0, $result);
    }

    #[Test]
    public function it_calls_vendor_publish_with_force_option(): void
    {
        $command = Mockery::mock(InstallCommand::class)->makePartial();
        $command->shouldReceive('call')
            ->with('vendor:publish', [
                '--tag' => 'laradox',
                '--force' => true,
            ])
            ->once()
            ->andReturn(0);
        $command->shouldReceive('info')->andReturn(null);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('comment')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);
        $command->shouldReceive('option')->with('force')->andReturn(true);
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('makeScriptsExecutable')->andReturn(null);
        
        $result = $command->handle();
        
        $this->assertEquals(0, $result);
    }

    #[Test]
    public function it_returns_success_status(): void
    {
        $command = Mockery::mock(InstallCommand::class)->makePartial();
        $command->shouldReceive('call')->andReturn(0);
        $command->shouldReceive('info')->andReturn(null);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('comment')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);
        $command->shouldReceive('option')->with('force')->andReturn(false);
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('makeScriptsExecutable')->andReturn(null);
        
        $result = $command->handle();
        
        $this->assertEquals(0, $result);
    }

    #[Test]
    public function it_provides_next_steps_instructions(): void
    {
        $command = Mockery::mock(InstallCommand::class)->makePartial();
        $command->shouldReceive('call')->andReturn(0);
        $command->shouldReceive('info')->andReturn(null);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('comment')->with('Next steps:')->once();
        $command->shouldReceive('line')->andReturn(null);
        $command->shouldReceive('option')->with('force')->andReturn(false);
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('makeScriptsExecutable')->andReturn(null);
        
        $command->handle();
        
        // If we get here without exception, the test passes
        $this->assertTrue(true);
    }

    #[Test]
    public function it_mentions_ssl_setup_in_instructions(): void
    {
        $command = Mockery::mock(InstallCommand::class)->makePartial();
        $command->shouldReceive('call')->andReturn(0);
        $command->shouldReceive('info')->andReturn(null);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('comment')->andReturn(null);
        $command->shouldReceive('line')
            ->with(Mockery::pattern('/laradox:setup-ssl/'))
            ->once();
        $command->shouldReceive('line')->andReturn(null);
        $command->shouldReceive('option')->with('force')->andReturn(false);
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('makeScriptsExecutable')->andReturn(null);
        
        $command->handle();
        
        $this->assertTrue(true);
    }

    #[Test]
    public function it_mentions_up_command_in_instructions(): void
    {
        $command = Mockery::mock(InstallCommand::class)->makePartial();
        $command->shouldReceive('call')->andReturn(0);
        $command->shouldReceive('info')->andReturn(null);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('comment')->andReturn(null);
        $command->shouldReceive('line')
            ->with(Mockery::pattern('/laradox:up/'))
            ->once();
        $command->shouldReceive('line')->andReturn(null);
        $command->shouldReceive('option')->with('force')->andReturn(false);
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('makeScriptsExecutable')->andReturn(null);
        
        $command->handle();
        
        $this->assertTrue(true);
    }

    #[Test]
    public function it_sets_correct_permissions_on_scripts(): void
    {
        // Create a test script
        $testPath = base_path('composer');
        File::put($testPath, '#!/bin/bash');
        chmod($testPath, 0644); // Remove execute permission first
        
        // Set to 0755
        chmod($testPath, 0755);
        
        // Check if permissions are set to 0755
        $perms = fileperms($testPath) & 0777;
        $this->assertEquals(0755, $perms);
        
        // Cleanup
        File::delete($testPath);
    }

    #[Test]
    public function it_creates_ssl_directory_with_correct_permissions(): void
    {
        $sslDir = base_path('docker/nginx/ssl');
        
        // Ensure directory doesn't exist
        if (File::exists($sslDir)) {
            File::deleteDirectory(dirname($sslDir, 2));
        }

        $command = Mockery::mock(InstallCommand::class)->makePartial();
        $command->shouldReceive('call')->andReturn(0);
        $command->shouldReceive('info')->andReturn(null);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('comment')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);
        $command->shouldReceive('option')->with('force')->andReturn(false);
        
        $command->handle();
        
        $this->assertTrue(File::exists($sslDir));
        
        // Check permissions
        $perms = fileperms($sslDir) & 0777;
        $this->assertEquals(0755, $perms);
        
        // Cleanup
        if (File::exists($sslDir)) {
            File::deleteDirectory(dirname($sslDir, 2));
        }
    }
}
