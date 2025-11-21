<?php

namespace Laradox\Tests\Unit;

use Laradox\Console\LogsCommand;
use Laradox\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LogsCommandTest extends TestCase
{
    #[Test]
    public function it_has_correct_signature(): void
    {
        $command = new LogsCommand();
        
        $this->assertStringContainsString('laradox:logs', $command->getName());
    }

    #[Test]
    public function it_has_correct_description(): void
    {
        $command = new LogsCommand();
        
        $this->assertEquals('View Laradox Docker container logs', $command->getDescription());
    }

    #[Test]
    public function it_accepts_service_argument(): void
    {
        $command = new LogsCommand();
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasArgument('service'));
        
        $argument = $definition->getArgument('service');
        $this->assertFalse($argument->isRequired());
    }

    #[Test]
    public function it_has_environment_option(): void
    {
        $command = new LogsCommand();
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasOption('environment'));
        
        $option = $definition->getOption('environment');
        $this->assertEquals('development', $option->getDefault());
    }

    #[Test]
    public function it_has_follow_option(): void
    {
        $command = new LogsCommand();
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasOption('follow'));
    }

    #[Test]
    public function it_has_tail_option(): void
    {
        $command = new LogsCommand();
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasOption('tail'));
        
        $option = $definition->getOption('tail');
        $this->assertTrue($option->acceptValue());
    }

    #[Test]
    public function it_has_timestamps_option(): void
    {
        $command = new LogsCommand();
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasOption('timestamps'));
    }

    #[Test]
    public function it_validates_all_required_options_are_present(): void
    {
        $command = new LogsCommand();
        $definition = $command->getDefinition();
        
        // Verify all expected options exist (excluding default Laravel command options)
        $expectedOptions = ['environment', 'follow', 'tail', 'timestamps'];
        
        foreach ($expectedOptions as $option) {
            $this->assertTrue(
                $definition->hasOption($option),
                "Expected option '{$option}' to exist"
            );
        }
    }

    #[Test]
    public function it_has_service_argument_as_optional(): void
    {
        $command = new LogsCommand();
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasArgument('service'));
        
        $argument = $definition->getArgument('service');
        $this->assertFalse($argument->isRequired(), 'Service argument should be optional');
    }

    #[Test]
    public function it_has_shortcut_for_follow_option(): void
    {
        $command = new LogsCommand();
        $definition = $command->getDefinition();
        
        $option = $definition->getOption('follow');
        $this->assertEquals('f', $option->getShortcut());
    }

    #[Test]
    public function it_extends_illuminate_command(): void
    {
        $command = new LogsCommand();
        
        $this->assertInstanceOf(\Illuminate\Console\Command::class, $command);
    }

    #[Test]
    public function environment_option_accepts_values(): void
    {
        $command = new LogsCommand();
        $definition = $command->getDefinition();
        
        $option = $definition->getOption('environment');
        $this->assertTrue($option->acceptValue());
    }

    #[Test]
    public function tail_option_accepts_numeric_values(): void
    {
        $command = new LogsCommand();
        $definition = $command->getDefinition();
        
        $option = $definition->getOption('tail');
        $this->assertTrue($option->acceptValue());
    }

    #[Test]
    public function timestamps_option_is_boolean(): void
    {
        $command = new LogsCommand();
        $definition = $command->getDefinition();
        
        $option = $definition->getOption('timestamps');
        $this->assertFalse($option->acceptValue());
    }

    #[Test]
    public function follow_option_is_boolean(): void
    {
        $command = new LogsCommand();
        $definition = $command->getDefinition();
        
        $option = $definition->getOption('follow');
        $this->assertFalse($option->acceptValue());
    }
}
