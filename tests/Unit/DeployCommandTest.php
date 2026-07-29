<?php

namespace Laradox\Tests\Unit;

use Laradox\Console\DeployCommand;
use Laradox\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Symfony\Component\Console\Input\ArrayInput;

class DeployCommandTest extends TestCase
{
    #[Test]
    public function it_has_correct_signature(): void
    {
        $command = new DeployCommand;

        $this->assertEquals('laradox:deploy', $command->getName());
    }

    #[Test]
    public function it_has_correct_description(): void
    {
        $command = new DeployCommand;

        $this->assertEquals(
            'Deploy the application: pull, build, migrate, optimize and health-check',
            $command->getDescription()
        );
    }

    #[Test]
    public function it_has_all_expected_options(): void
    {
        $definition = (new DeployCommand)->getDefinition();

        $expected = [
            'file', 'environment', 'service', 'node-service', 'no-pull', 'no-build', 'no-composer',
            'no-assets', 'no-migrate', 'no-optimize', 'maintenance', 'timeout', 'dry-run', 'force',
        ];

        foreach ($expected as $option) {
            $this->assertTrue($definition->hasOption($option), "Expected option '{$option}' to exist");
        }
    }

    #[Test]
    public function it_defaults_to_the_production_environment(): void
    {
        $definition = (new DeployCommand)->getDefinition();

        $this->assertEquals('production', $definition->getOption('environment')->getDefault());
    }

    #[Test]
    public function it_defaults_to_a_two_minute_health_timeout(): void
    {
        $definition = (new DeployCommand)->getDefinition();

        $this->assertEquals('120', $definition->getOption('timeout')->getDefault());
    }

    #[Test]
    public function it_file_option_has_shortcut_f(): void
    {
        $definition = (new DeployCommand)->getDefinition();

        $this->assertEquals('f', $definition->getOption('file')->getShortcut());
    }

    #[Test]
    public function it_builds_the_full_deployment_plan_in_order(): void
    {
        $this->assertEquals([
            'Pull latest code',
            'Build images',
            'Start containers',
            'Wait for healthy services',
            'Install PHP dependencies',
            'Build front-end assets',
            'Run database migrations',
            'Warm up caches',
        ], $this->planLabels());
    }

    #[Test]
    public function each_skip_option_removes_its_step(): void
    {
        $skips = [
            'no-pull' => 'Pull latest code',
            'no-build' => 'Build images',
            'no-composer' => 'Install PHP dependencies',
            'no-assets' => 'Build front-end assets',
            'no-migrate' => 'Run database migrations',
            'no-optimize' => 'Warm up caches',
        ];

        foreach ($skips as $option => $label) {
            $this->assertNotContains(
                $label,
                $this->planLabels([$option => true]),
                "Expected --{$option} to remove the '{$label}' step"
            );
        }
    }

    #[Test]
    public function starting_containers_and_the_health_gate_can_never_be_skipped(): void
    {
        $labels = $this->planLabels([
            'no-pull' => true,
            'no-build' => true,
            'no-composer' => true,
            'no-assets' => true,
            'no-migrate' => true,
            'no-optimize' => true,
        ]);

        $this->assertEquals(['Start containers', 'Wait for healthy services'], $labels);
    }

    #[Test]
    public function maintenance_mode_wraps_the_deployment(): void
    {
        $labels = $this->planLabels(['maintenance' => true]);

        $this->assertEquals('Enable maintenance mode', $labels[1]);
        $this->assertEquals('Disable maintenance mode', end($labels));
    }

    #[Test]
    public function maintenance_mode_is_engaged_before_the_images_are_built(): void
    {
        $labels = $this->planLabels(['maintenance' => true]);

        $this->assertLessThan(
            array_search('Build images', $labels, true),
            array_search('Enable maintenance mode', $labels, true)
        );
    }

    #[Test]
    public function the_health_gate_runs_before_migrations(): void
    {
        $labels = $this->planLabels();

        $this->assertLessThan(
            array_search('Run database migrations', $labels, true),
            array_search('Wait for healthy services', $labels, true)
        );
    }

    #[Test]
    public function it_uses_the_docker_traits(): void
    {
        $command = new DeployCommand;

        $this->assertTrue(method_exists($command, 'checkDocker'), 'DeployCommand should use ChecksDocker');
        $this->assertTrue(method_exists($command, 'waitForHealthyServices'), 'DeployCommand should use InspectsContainers');
    }

    #[Test]
    public function it_extends_illuminate_command(): void
    {
        $this->assertInstanceOf(\Illuminate\Console\Command::class, new DeployCommand);
    }

    /**
     * Build the deployment plan the command would run.
     *
     * @param  array<string, mixed>  $options
     * @return array<int, array{label: string, detail: string}>
     */
    private function plan(array $options = []): array
    {
        $command = new DeployCommand;

        $input = new ArrayInput([], $command->getDefinition());
        foreach ($options as $option => $value) {
            $input->setOption($option, $value);
        }
        $command->setInput($input);

        return (new ReflectionMethod($command, 'buildPlan'))
            ->invoke($command, base_path('docker-compose.production.yml'), 'production', 120);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, string>
     */
    private function planLabels(array $options = []): array
    {
        return array_map(fn (array $step): string => $step['label'], $this->plan($options));
    }
}
