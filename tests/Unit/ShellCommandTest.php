<?php

namespace Laradox\Tests\Unit;

use Laradox\Console\ShellCommand;
use Laradox\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ShellCommandTest extends TestCase
{
    #[Test]
    public function it_has_correct_signature(): void
    {
        $command = new ShellCommand();
        
        $this->assertStringContainsString('laradox:shell', $command->getName());
    }

    #[Test]
    public function it_has_correct_description(): void
    {
        $command = new ShellCommand();
        
        $this->assertEquals('Enter a container interactively', $command->getDescription());
    }

    #[Test]
    public function it_accepts_service_argument(): void
    {
        $command = new ShellCommand();
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasArgument('service'));
        
        $argument = $definition->getArgument('service');
        $this->assertFalse($argument->isRequired());
        $this->assertEquals('php', $argument->getDefault());
    }

    #[Test]
    public function it_has_environment_option(): void
    {
        $command = new ShellCommand();
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasOption('environment'));
        
        $option = $definition->getOption('environment');
        $this->assertEquals('development', $option->getDefault());
    }

    #[Test]
    public function it_has_user_option(): void
    {
        $command = new ShellCommand();
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasOption('user'));
        
        $option = $definition->getOption('user');
        $this->assertTrue($option->acceptValue());
    }

    #[Test]
    public function it_has_shell_option(): void
    {
        $command = new ShellCommand();
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasOption('shell'));
        
        $option = $definition->getOption('shell');
        $this->assertEquals('sh', $option->getDefault());
    }

    #[Test]
    public function it_validates_all_required_options_are_present(): void
    {
        $command = new ShellCommand();
        $definition = $command->getDefinition();
        
        // Verify all expected options exist
        $expectedOptions = ['environment', 'user', 'shell'];
        
        foreach ($expectedOptions as $option) {
            $this->assertTrue(
                $definition->hasOption($option),
                "Expected option '{$option}' to exist"
            );
        }
    }

    #[Test]
    public function it_has_service_argument_with_default_value(): void
    {
        $command = new ShellCommand();
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasArgument('service'));
        
        $argument = $definition->getArgument('service');
        $this->assertEquals('php', $argument->getDefault());
    }

    #[Test]
    public function it_extends_illuminate_command(): void
    {
        $command = new ShellCommand();
        
        $this->assertInstanceOf(\Illuminate\Console\Command::class, $command);
    }

    #[Test]
    public function environment_option_accepts_values(): void
    {
        $command = new ShellCommand();
        $definition = $command->getDefinition();
        
        $option = $definition->getOption('environment');
        $this->assertTrue($option->acceptValue());
    }

    #[Test]
    public function user_option_is_optional(): void
    {
        $command = new ShellCommand();
        $definition = $command->getDefinition();
        
        $option = $definition->getOption('user');
        $this->assertNull($option->getDefault());
    }

    #[Test]
    public function shell_option_has_sh_default(): void
    {
        $command = new ShellCommand();
        $definition = $command->getDefinition();
        
        $option = $definition->getOption('shell');
        $this->assertEquals('sh', $option->getDefault());
    }

    #[Test]
    public function service_argument_defaults_to_php(): void
    {
        $command = new ShellCommand();
        $definition = $command->getDefinition();
        
        $argument = $definition->getArgument('service');
        $this->assertEquals('php', $argument->getDefault());
    }

    #[Test]
    public function it_uses_checks_docker_trait(): void
    {
        $command = new ShellCommand();
        
        $this->assertTrue(
            method_exists($command, 'checkDocker'),
            'ShellCommand should use ChecksDocker trait'
        );
    }

    #[Test]
    public function it_has_is_service_running_method(): void
    {
        $command = new ShellCommand();
        
        $this->assertTrue(
            method_exists($command, 'isServiceRunning'),
            'ShellCommand should have isServiceRunning method'
        );
    }

    #[Test]
    public function it_has_detect_available_shell_method(): void
    {
        $command = new ShellCommand();
        
        $this->assertTrue(
            method_exists($command, 'detectAvailableShell'),
            'ShellCommand should have detectAvailableShell method'
        );
    }
}
