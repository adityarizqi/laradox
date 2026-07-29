<?php

namespace Laradox\Tests\Unit;

use Laradox\Console\StatusCommand;
use Laradox\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class StatusCommandTest extends TestCase
{
    #[Test]
    public function it_has_correct_signature(): void
    {
        $command = new StatusCommand;

        $this->assertEquals('laradox:status', $command->getName());
    }

    #[Test]
    public function it_has_correct_description(): void
    {
        $command = new StatusCommand;

        $this->assertEquals('Show Laradox service health and resource usage', $command->getDescription());
    }

    #[Test]
    public function it_accepts_an_optional_service_argument(): void
    {
        $definition = (new StatusCommand)->getDefinition();

        $this->assertTrue($definition->hasArgument('service'));
        $this->assertFalse($definition->getArgument('service')->isRequired());
        $this->assertNull($definition->getArgument('service')->getDefault());
    }

    #[Test]
    public function it_has_all_expected_options(): void
    {
        $definition = (new StatusCommand)->getDefinition();

        foreach (['file', 'environment', 'stats', 'watch', 'interval', 'json'] as $option) {
            $this->assertTrue($definition->hasOption($option), "Expected option '{$option}' to exist");
        }
    }

    #[Test]
    public function it_defaults_to_the_development_environment(): void
    {
        $definition = (new StatusCommand)->getDefinition();

        $this->assertEquals('development', $definition->getOption('environment')->getDefault());
    }

    #[Test]
    public function it_defaults_to_a_five_second_watch_interval(): void
    {
        $definition = (new StatusCommand)->getDefinition();

        $this->assertEquals('5', $definition->getOption('interval')->getDefault());
    }

    #[Test]
    public function stats_watch_and_json_are_flags(): void
    {
        $definition = (new StatusCommand)->getDefinition();

        foreach (['stats', 'watch', 'json'] as $flag) {
            $this->assertFalse($definition->getOption($flag)->acceptValue(), "Expected '{$flag}' to be a flag");
        }
    }

    #[Test]
    public function it_file_option_has_shortcut_f(): void
    {
        $definition = (new StatusCommand)->getDefinition();

        $this->assertEquals('f', $definition->getOption('file')->getShortcut());
    }

    #[Test]
    public function it_uses_the_docker_traits(): void
    {
        $command = new StatusCommand;

        $this->assertTrue(method_exists($command, 'checkDocker'), 'StatusCommand should use ChecksDocker');
        $this->assertTrue(method_exists($command, 'getServiceStatuses'), 'StatusCommand should use InspectsContainers');
    }

    #[Test]
    public function it_extends_illuminate_command(): void
    {
        $this->assertInstanceOf(\Illuminate\Console\Command::class, new StatusCommand);
    }
}
