<?php

namespace Laradox\Tests\Unit;

use Illuminate\Support\Facades\File;
use Laradox\Console\BenchmarkCommand;
use Laradox\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;

class BenchmarkCommandTest extends TestCase
{
    #[Test]
    public function it_has_correct_signature(): void
    {
        $command = new BenchmarkCommand;

        $this->assertEquals('laradox:benchmark', $command->getName());
    }

    #[Test]
    public function it_has_correct_description(): void
    {
        $command = new BenchmarkCommand;

        $this->assertEquals(
            'Benchmark the application over HTTP and report latency percentiles',
            $command->getDescription()
        );
    }

    #[Test]
    public function it_accepts_an_optional_url_argument(): void
    {
        $definition = (new BenchmarkCommand)->getDefinition();

        $this->assertTrue($definition->hasArgument('url'));
        $this->assertFalse($definition->getArgument('url')->isRequired());
    }

    #[Test]
    public function it_has_all_expected_options(): void
    {
        $definition = (new BenchmarkCommand)->getDefinition();

        foreach (['requests', 'concurrency', 'warmup', 'timeout', 'insecure', 'json'] as $option) {
            $this->assertTrue($definition->hasOption($option), "Expected option '{$option}' to exist");
        }
    }

    #[Test]
    public function it_has_sensible_load_defaults(): void
    {
        $definition = (new BenchmarkCommand)->getDefinition();

        $this->assertEquals('200', $definition->getOption('requests')->getDefault());
        $this->assertEquals('10', $definition->getOption('concurrency')->getDefault());
        $this->assertEquals('10', $definition->getOption('warmup')->getDefault());
        $this->assertEquals('10', $definition->getOption('timeout')->getDefault());
    }

    #[Test]
    public function it_does_not_reuse_the_reserved_n_shortcut(): void
    {
        $definition = (new BenchmarkCommand)->getDefinition();

        // -n is Symfony's --no-interaction shortcut; reusing it breaks the command.
        $this->assertNull($definition->getOption('requests')->getShortcut());
    }

    #[Test]
    public function it_builds_an_http_url_when_no_certificates_exist(): void
    {
        $this->assertEquals('http://test.docker.localhost', $this->defaultUrl());
    }

    #[Test]
    public function it_builds_an_https_url_when_certificates_exist(): void
    {
        File::ensureDirectoryExists(dirname(config('laradox.ssl.cert_path')));
        File::put(config('laradox.ssl.cert_path'), 'dummy cert');
        File::put(config('laradox.ssl.key_path'), 'dummy key');

        try {
            $this->assertEquals('https://test.docker.localhost', $this->defaultUrl());
        } finally {
            File::deleteDirectory(base_path('docker'));
        }
    }

    #[Test]
    public function it_appends_non_standard_ports_to_the_default_url(): void
    {
        config()->set('laradox.ports.http', 8080);

        $this->assertEquals('http://test.docker.localhost:8080', $this->defaultUrl());
    }

    #[Test]
    public function it_computes_nearest_rank_percentiles(): void
    {
        $sorted = [10.0, 20.0, 30.0, 40.0, 50.0, 60.0, 70.0, 80.0, 90.0, 100.0];

        $this->assertEquals(10.0, $this->percentile($sorted, 0));
        $this->assertEquals(50.0, $this->percentile($sorted, 50));
        $this->assertEquals(90.0, $this->percentile($sorted, 90));
        $this->assertEquals(100.0, $this->percentile($sorted, 100));
    }

    #[Test]
    public function it_returns_zero_percentiles_for_an_empty_sample(): void
    {
        $this->assertEquals(0.0, $this->percentile([], 95));
    }

    #[Test]
    public function it_counts_only_sub_400_responses_as_successful(): void
    {
        $method = new ReflectionMethod(BenchmarkCommand::class, 'countSuccessful');

        $successful = $method->invoke(new BenchmarkCommand, [
            ['code' => 200, 'error' => null],
            ['code' => 302, 'error' => null],
            ['code' => 404, 'error' => null],
            ['code' => 500, 'error' => null],
            ['code' => 0, 'error' => 'Connection refused'],
        ]);

        $this->assertEquals(2, $successful);
    }

    #[Test]
    public function it_summarizes_a_run(): void
    {
        $method = new ReflectionMethod(BenchmarkCommand::class, 'summarize');

        $report = $method->invoke(
            new BenchmarkCommand,
            'http://example.test',
            [
                ['code' => 200, 'time' => 10.0, 'size' => 1024, 'error' => null],
                ['code' => 200, 'time' => 30.0, 'size' => 1024, 'error' => null],
                ['code' => 500, 'time' => 20.0, 'size' => 512, 'error' => null],
                ['code' => 0, 'time' => 0.0, 'size' => 0, 'error' => 'Connection refused'],
            ],
            2.0,
            ['requests' => 4, 'concurrency' => 2, 'warmup' => 0, 'timeout' => 10, 'insecure' => false]
        );

        $this->assertEquals('http://example.test', $report['target']);
        $this->assertEquals(4, $report['requests']);
        $this->assertEquals(2.0, $report['requests_per_second']);
        $this->assertEquals(2, $report['successful']);
        $this->assertEquals(2, $report['failed']);
        $this->assertEquals(2560, $report['bytes']);
        $this->assertEquals(30.0, $report['latency_ms']['max']);
        $this->assertEquals(2, $report['status_codes'][200]);
        $this->assertEquals(1, $report['errors']['Connection refused']);
    }

    #[Test]
    public function it_formats_byte_counts(): void
    {
        $method = new ReflectionMethod(BenchmarkCommand::class, 'formatBytes');
        $command = new BenchmarkCommand;

        $this->assertEquals('512 B', $method->invoke($command, 512));
        $this->assertEquals('1 KB', $method->invoke($command, 1024));
        $this->assertEquals('1.5 MB', $method->invoke($command, 1572864));
    }

    #[Test]
    public function it_extends_illuminate_command(): void
    {
        $this->assertInstanceOf(\Illuminate\Console\Command::class, new BenchmarkCommand);
    }

    private function defaultUrl(): string
    {
        return (new ReflectionMethod(BenchmarkCommand::class, 'defaultUrl'))->invoke(new BenchmarkCommand);
    }

    /**
     * @param  array<int, float>  $sorted
     */
    private function percentile(array $sorted, float $percentile): float
    {
        return (new ReflectionMethod(BenchmarkCommand::class, 'percentile'))
            ->invoke(new BenchmarkCommand, $sorted, $percentile);
    }
}
