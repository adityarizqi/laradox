<?php

namespace Laradox\Tests\Feature;

use Laradox\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class BenchmarkCommandTest extends FeatureTestCase
{
    #[Test]
    public function it_rejects_a_request_count_below_one(): void
    {
        $this->artisan('laradox:benchmark', ['--requests' => '0'])
            ->expectsOutput('The --requests option must be a positive integer.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_rejects_a_non_numeric_concurrency(): void
    {
        $this->artisan('laradox:benchmark', ['--concurrency' => 'many'])
            ->expectsOutput('The --concurrency option must be a positive integer.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_rejects_a_negative_warmup(): void
    {
        $this->artisan('laradox:benchmark', ['--warmup' => '-1'])
            ->expectsOutput('The --warmup option must be zero or a positive integer.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_rejects_a_timeout_below_one(): void
    {
        $this->artisan('laradox:benchmark', ['--timeout' => '0'])
            ->expectsOutput('The --timeout option must be a positive integer.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_rejects_an_invalid_url(): void
    {
        $this->artisan('laradox:benchmark', ['url' => 'not a url'])
            ->expectsOutput('✗ Invalid URL: not a url')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_reports_an_unreachable_target(): void
    {
        // Port 1 is never bound, so the connection is refused immediately.
        $this->artisan('laradox:benchmark', [
            'url' => 'http://127.0.0.1:1/',
            '--requests' => '1',
            '--warmup' => '1',
            '--timeout' => '1',
        ])
            ->expectsOutputToContain('No successful response from http://127.0.0.1:1/')
            ->assertExitCode(1);
    }
}
