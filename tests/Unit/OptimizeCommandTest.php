<?php

namespace Laradox\Tests\Unit;

use Laradox\Console\OptimizeCommand;
use Laradox\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Symfony\Component\Console\Input\ArrayInput;

class OptimizeCommandTest extends TestCase
{
    #[Test]
    public function it_has_correct_signature(): void
    {
        $command = new OptimizeCommand;

        $this->assertEquals('laradox:optimize', $command->getName());
    }

    #[Test]
    public function it_has_correct_description(): void
    {
        $command = new OptimizeCommand;

        $this->assertEquals(
            'Build (or clear) the production caches inside the running containers',
            $command->getDescription()
        );
    }

    #[Test]
    public function it_has_all_expected_options(): void
    {
        $definition = (new OptimizeCommand)->getDefinition();

        foreach (['file', 'environment', 'service', 'clear', 'skip-autoloader', 'skip-reload', 'force'] as $option) {
            $this->assertTrue($definition->hasOption($option), "Expected option '{$option}' to exist");
        }
    }

    #[Test]
    public function it_defaults_to_the_production_environment(): void
    {
        $definition = (new OptimizeCommand)->getDefinition();

        $this->assertEquals('production', $definition->getOption('environment')->getDefault());
    }

    #[Test]
    public function it_defaults_to_the_php_service(): void
    {
        $definition = (new OptimizeCommand)->getDefinition();

        $this->assertEquals('php', $definition->getOption('service')->getDefault());
    }

    #[Test]
    public function it_file_option_has_shortcut_f(): void
    {
        $definition = (new OptimizeCommand)->getDefinition();

        $this->assertEquals('f', $definition->getOption('file')->getShortcut());
    }

    #[Test]
    public function it_builds_the_full_optimization_pipeline(): void
    {
        $this->assertEquals([
            'Dumping optimized Composer autoloader',
            'Caching configuration',
            'Caching routes',
            'Caching views',
            'Caching events',
            'Reloading Octane workers',
        ], $this->stepLabels('production'));
    }

    #[Test]
    public function it_only_passes_no_dev_to_composer_in_production(): void
    {
        $production = $this->steps('production');
        $development = $this->steps('development');

        $this->assertContains('--no-dev', $production[0]['command']);
        $this->assertNotContains('--no-dev', $development[0]['command']);
    }

    #[Test]
    public function the_clear_mode_only_clears_and_reloads(): void
    {
        $this->assertEquals([
            'Clearing cached bootstrap files',
            'Reloading Octane workers',
        ], $this->stepLabels('production', ['clear' => true]));
    }

    #[Test]
    public function it_can_skip_the_autoloader_and_the_reload(): void
    {
        $labels = $this->stepLabels('production', [
            'skip-autoloader' => true,
            'skip-reload' => true,
        ]);

        $this->assertNotContains('Dumping optimized Composer autoloader', $labels);
        $this->assertNotContains('Reloading Octane workers', $labels);
        $this->assertContains('Caching configuration', $labels);
    }

    #[Test]
    public function the_octane_reload_is_optional(): void
    {
        $steps = $this->steps('production');
        $reload = end($steps);

        $this->assertEquals('Reloading Octane workers', $reload['label']);
        $this->assertTrue($reload['optional']);
    }

    #[Test]
    public function every_step_runs_through_artisan_or_composer(): void
    {
        foreach ($this->steps('production') as $step) {
            $this->assertContains($step['command'][0], ['php', 'composer']);
        }
    }

    #[Test]
    public function it_uses_the_docker_traits(): void
    {
        $command = new OptimizeCommand;

        $this->assertTrue(method_exists($command, 'checkDocker'), 'OptimizeCommand should use ChecksDocker');
        $this->assertTrue(method_exists($command, 'runInContainer'), 'OptimizeCommand should use InspectsContainers');
    }

    #[Test]
    public function it_extends_illuminate_command(): void
    {
        $this->assertInstanceOf(\Illuminate\Console\Command::class, new OptimizeCommand);
    }

    /**
     * Build the step list the command would run for an environment.
     *
     * @param  array<string, mixed>  $options
     * @return array<int, array{label: string, command: array<int, string>, optional?: bool}>
     */
    private function steps(string $env, array $options = []): array
    {
        $command = new OptimizeCommand;

        $input = new ArrayInput([], $command->getDefinition());
        foreach ($options as $option => $value) {
            $input->setOption($option, $value);
        }
        $command->setInput($input);

        return (new ReflectionMethod($command, 'buildSteps'))->invoke($command, $env);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    private function stepLabels(string $env, array $options = []): array
    {
        return array_map(fn (array $step): string => $step['label'], $this->steps($env, $options));
    }
}
