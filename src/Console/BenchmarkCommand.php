<?php

namespace Laradox\Console;

use Illuminate\Console\Command;

class BenchmarkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradox:benchmark
                            {url? : URL to benchmark (defaults to the configured Laradox domain)}
                            {--requests=200 : Total number of requests to send}
                            {--concurrency=10 : Number of requests kept in flight}
                            {--warmup=10 : Warm-up requests, excluded from the results}
                            {--timeout=10 : Per-request timeout in seconds}
                            {--insecure : Do not verify the TLS certificate}
                            {--json : Output the results as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Benchmark the application over HTTP and report latency percentiles';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! function_exists('curl_multi_init')) {
            $this->error('✗ The cURL extension is required to run benchmarks.');
            $this->line('Install ext-curl for your host PHP and try again.');

            return self::FAILURE;
        }

        $options = $this->validatedOptions();

        if ($options === null) {
            return self::FAILURE;
        }

        $url = $this->argument('url') ?: $this->defaultUrl();

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error("✗ Invalid URL: {$url}");

            return self::FAILURE;
        }

        if (! $this->option('json')) {
            $this->newLine();
            $this->info("Benchmarking {$url}");
            $this->line(sprintf(
                '%d requests · concurrency %d · warm-up %d · timeout %ds',
                $options['requests'],
                $options['concurrency'],
                $options['warmup'],
                $options['timeout']
            ));
            $this->newLine();
        }

        if ($options['warmup'] > 0) {
            $warmup = $this->dispatch($url, $options['warmup'], min($options['concurrency'], $options['warmup']), $options);

            // A target that fails every warm-up request is not worth measuring.
            if ($this->countSuccessful($warmup) === 0) {
                return $this->reportUnreachable($url, $warmup);
            }
        }

        $startedAt = microtime(true);
        $results = $this->dispatch($url, $options['requests'], $options['concurrency'], $options);
        $duration = microtime(true) - $startedAt;

        $report = $this->summarize($url, $results, $duration, $options);

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->renderReport($report);
        }

        return $report['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Read and validate the numeric options.
     *
     * @return array{requests: int, concurrency: int, warmup: int, timeout: int, insecure: bool}|null
     */
    protected function validatedOptions(): ?array
    {
        $numeric = [
            'requests' => ['min' => 1, 'label' => 'The --requests option must be a positive integer.'],
            'concurrency' => ['min' => 1, 'label' => 'The --concurrency option must be a positive integer.'],
            'warmup' => ['min' => 0, 'label' => 'The --warmup option must be zero or a positive integer.'],
            'timeout' => ['min' => 1, 'label' => 'The --timeout option must be a positive integer.'],
        ];

        $values = [];

        foreach ($numeric as $option => $rules) {
            $value = $this->option($option);

            if (! is_numeric($value) || (int) $value < $rules['min'] || (float) $value != (int) $value) {
                $this->error($rules['label']);

                return null;
            }

            $values[$option] = (int) $value;
        }

        // More workers than requests just leaves connections idle.
        $values['concurrency'] = min($values['concurrency'], $values['requests']);
        $values['insecure'] = (bool) $this->option('insecure');

        return $values;
    }

    /**
     * Build the default target from the Laradox domain and SSL configuration.
     */
    protected function defaultUrl(): string
    {
        $domain = config('laradox.domain', 'localhost');
        $certPath = config('laradox.ssl.cert_path');
        $keyPath = config('laradox.ssl.key_path');

        $secure = is_string($certPath) && is_string($keyPath)
            && file_exists($certPath) && file_exists($keyPath);

        $port = (int) config($secure ? 'laradox.ports.https' : 'laradox.ports.http', $secure ? 443 : 80);
        $default = $secure ? 443 : 80;

        return sprintf(
            '%s://%s%s',
            $secure ? 'https' : 'http',
            $domain,
            $port === $default ? '' : ":{$port}"
        );
    }

    /**
     * Send the requested number of requests, keeping `concurrency` in flight.
     *
     * @param  array{timeout: int, insecure: bool}  $options
     * @return array<int, array{code: int, time: float, size: int, error: string|null}>
     */
    protected function dispatch(string $url, int $requests, int $concurrency, array $options): array
    {
        $multi = curl_multi_init();
        $results = [];
        $queued = 0;

        $enqueue = function () use ($multi, $url, $options, &$queued, $requests): void {
            if ($queued >= $requests) {
                return;
            }

            $handle = curl_init();
            curl_setopt_array($handle, $this->curlOptions($url, $options));
            curl_multi_add_handle($multi, $handle);
            $queued++;
        };

        for ($i = 0; $i < $concurrency; $i++) {
            $enqueue();
        }

        do {
            curl_multi_exec($multi, $active);

            if ($active) {
                curl_multi_select($multi, 0.1);
            }

            $completedThisPass = 0;

            while ($completed = curl_multi_info_read($multi)) {
                $handle = $completed['handle'];
                $completedThisPass++;

                $results[] = [
                    'code' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
                    'time' => (float) curl_getinfo($handle, CURLINFO_TOTAL_TIME) * 1000,
                    'size' => (int) curl_getinfo($handle, CURLINFO_SIZE_DOWNLOAD),
                    'error' => $completed['result'] === CURLE_OK ? null : curl_error($handle),
                ];

                curl_multi_remove_handle($multi, $handle);

                // Backfill immediately so the window stays saturated.
                $enqueue();
            }

            // Nothing in flight, nothing completed and nothing left to queue:
            // stop rather than spin if a handle failed to be added.
            if (! $active && $completedThisPass === 0 && $queued >= $requests) {
                break;
            }
        } while ($active || count($results) < $requests);

        curl_multi_close($multi);

        return $results;
    }

    /**
     * cURL options shared by every request.
     *
     * @param  array{timeout: int, insecure: bool}  $options
     * @return array<int, mixed>
     */
    protected function curlOptions(string $url, array $options): array
    {
        return [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => $options['timeout'],
            CURLOPT_CONNECTTIMEOUT => $options['timeout'],
            CURLOPT_SSL_VERIFYPEER => ! $options['insecure'],
            CURLOPT_SSL_VERIFYHOST => $options['insecure'] ? 0 : 2,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'Laradox-Benchmark',
        ];
    }

    /**
     * Turn raw request results into the report payload.
     *
     * @param  array<int, array{code: int, time: float, size: int, error: string|null}>  $results
     * @param  array{requests: int, concurrency: int, warmup: int, timeout: int, insecure: bool}  $options
     * @return array<string, mixed>
     */
    protected function summarize(string $url, array $results, float $duration, array $options): array
    {
        $latencies = array_map(fn (array $result): float => $result['time'], $results);
        sort($latencies);

        $successful = $this->countSuccessful($results);
        $bytes = array_sum(array_map(fn (array $result): int => $result['size'], $results));
        $count = count($results);

        $statusCodes = [];
        $errors = [];

        foreach ($results as $result) {
            if ($result['error'] !== null) {
                $errors[$result['error']] = ($errors[$result['error']] ?? 0) + 1;

                continue;
            }

            $statusCodes[$result['code']] = ($statusCodes[$result['code']] ?? 0) + 1;
        }

        ksort($statusCodes);
        arsort($errors);

        return [
            'target' => $url,
            'requests' => $count,
            'concurrency' => $options['concurrency'],
            'duration_seconds' => round($duration, 3),
            'requests_per_second' => $duration > 0 ? round($count / $duration, 2) : 0.0,
            'successful' => $successful,
            'failed' => $count - $successful,
            'bytes' => $bytes,
            'latency_ms' => [
                'min' => $this->percentile($latencies, 0),
                'avg' => $latencies === [] ? 0.0 : round(array_sum($latencies) / count($latencies), 2),
                'p50' => $this->percentile($latencies, 50),
                'p90' => $this->percentile($latencies, 90),
                'p95' => $this->percentile($latencies, 95),
                'p99' => $this->percentile($latencies, 99),
                'max' => $this->percentile($latencies, 100),
            ],
            'status_codes' => $statusCodes,
            'errors' => $errors,
        ];
    }

    /**
     * Count the responses that came back below the 4xx range.
     *
     * @param  array<int, array{code: int, error: string|null}>  $results
     */
    protected function countSuccessful(array $results): int
    {
        return count(array_filter(
            $results,
            fn (array $result): bool => $result['error'] === null && $result['code'] > 0 && $result['code'] < 400
        ));
    }

    /**
     * Nearest-rank percentile over a pre-sorted list.
     *
     * @param  array<int, float>  $sorted
     */
    protected function percentile(array $sorted, float $percentile): float
    {
        if ($sorted === []) {
            return 0.0;
        }

        $index = (int) ceil($percentile / 100 * count($sorted)) - 1;

        return round($sorted[max(0, min($index, count($sorted) - 1))], 2);
    }

    /**
     * Render the human-readable report.
     *
     * @param  array<string, mixed>  $report
     */
    protected function renderReport(array $report): void
    {
        $transferRate = $report['duration_seconds'] > 0
            ? $this->formatBytes((int) round($report['bytes'] / $report['duration_seconds'])).'/s'
            : '-';

        $this->table(['Metric', 'Value'], [
            ['Target', $report['target']],
            ['Requests', $report['requests'].' (concurrency '.$report['concurrency'].')'],
            ['Duration', $report['duration_seconds'].'s'],
            ['Throughput', $report['requests_per_second'].' req/s'],
            ['Successful', sprintf(
                '%d (%.1f%%)',
                $report['successful'],
                $report['requests'] > 0 ? $report['successful'] / $report['requests'] * 100 : 0
            )],
            ['Failed', $report['failed']],
            ['Transferred', $this->formatBytes($report['bytes']).' ('.$transferRate.')'],
        ]);

        $latency = $report['latency_ms'];

        $this->newLine();
        $this->line('<info>Latency (ms)</info>');
        $this->table(
            ['min', 'avg', 'p50', 'p90', 'p95', 'p99', 'max'],
            [[$latency['min'], $latency['avg'], $latency['p50'], $latency['p90'], $latency['p95'], $latency['p99'], $latency['max']]]
        );

        if ($report['status_codes'] !== []) {
            $this->line('<info>Status codes</info>');
            foreach ($report['status_codes'] as $code => $count) {
                $this->line("  {$code} × {$count}");
            }
        }

        if ($report['errors'] !== []) {
            $this->newLine();
            $this->line('<error>Errors</error>');
            foreach ($report['errors'] as $message => $count) {
                $this->line("  {$count} × {$message}");
            }
        }

        $this->newLine();
    }

    /**
     * Explain that nothing answered on the target URL.
     *
     * @param  array<int, array{code: int, error: string|null}>  $warmup
     */
    protected function reportUnreachable(string $url, array $warmup): int
    {
        $reason = null;

        foreach ($warmup as $result) {
            if ($result['error'] !== null) {
                $reason = $result['error'];

                break;
            }
        }

        $this->newLine();
        $this->error("✗ No successful response from {$url}");

        if ($reason !== null) {
            $this->line("Reason: {$reason}");
        }

        $this->newLine();
        $this->line('Check that the containers are up and the domain resolves:');
        $this->comment('  php artisan laradox:status');
        $this->comment('  php artisan laradox:up --detach');
        $this->line('For a self-signed development certificate, re-run with --insecure.');
        $this->newLine();

        return self::FAILURE;
    }

    /**
     * Format a byte count for humans.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $value = (float) $bytes;
        $unit = 0;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        return round($value, $unit === 0 ? 0 : 2).' '.$units[$unit];
    }
}
