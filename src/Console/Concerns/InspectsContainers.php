<?php

namespace Laradox\Console\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Read-only introspection of the containers behind a Compose project.
 *
 * Everything here goes through `docker inspect` / `docker stats` rather than
 * `docker compose ps --format json`, because the JSON formatter is missing on
 * Compose V1 and its field names moved around between V2 minors. Inspecting
 * container IDs works the same everywhere.
 *
 * Requires the ChecksDocker trait for compose command/service resolution.
 */
trait InspectsContainers
{
    /**
     * Build a status row for every service declared in the compose file.
     *
     * Services that have no container yet are reported as "not created" so the
     * report describes the whole project, not just what happens to be up.
     *
     * @return array<int, array{service: string, container: string|null, state: string, health: string, uptime: string, ports: string, exit_code: int|null}>
     */
    protected function getServiceStatuses(string $composeFile): array
    {
        $rows = [];

        foreach ($this->inspectContainers($this->getContainerIds($composeFile)) as $container) {
            $row = $this->normalizeContainer($container);

            if ($row !== null) {
                $rows[$row['service']] = $row;
            }
        }

        foreach ($this->getAvailableServices($composeFile) as $service) {
            $rows[$service] ??= [
                'service' => $service,
                'container' => null,
                'state' => 'not created',
                'health' => 'n/a',
                'uptime' => '-',
                'ports' => '-',
                'exit_code' => null,
            ];
        }

        ksort($rows);

        return array_values($rows);
    }

    /**
     * Get the container IDs of the project, including stopped containers.
     *
     * @return array<int, string>
     */
    protected function getContainerIds(string $composeFile): array
    {
        $command = sprintf(
            '%s -f %s ps --all --quiet 2>/dev/null',
            $this->getDockerComposeCommand(),
            escapeshellarg($composeFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            return [];
        }

        return array_values(array_filter(array_map('trim', $output)));
    }

    /**
     * Inspect the given containers.
     *
     * @param  array<int, string>  $ids
     * @return array<int, array<string, mixed>>
     */
    protected function inspectContainers(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $command = sprintf(
            'docker inspect --format %s %s 2>/dev/null',
            escapeshellarg('{{json .}}'),
            implode(' ', array_map('escapeshellarg', $ids))
        );

        exec($command, $output, $returnCode);

        $containers = [];

        foreach ($output as $line) {
            $decoded = json_decode($line, true);

            if (is_array($decoded)) {
                $containers[] = $decoded;
            }
        }

        return $containers;
    }

    /**
     * Reduce a `docker inspect` payload to the fields the reports care about.
     *
     * @param  array<string, mixed>  $container
     * @return array{service: string, container: string, state: string, health: string, uptime: string, ports: string, exit_code: int|null}|null
     */
    protected function normalizeContainer(array $container): ?array
    {
        $service = $container['Config']['Labels']['com.docker.compose.service'] ?? null;

        if (! is_string($service) || $service === '') {
            return null;
        }

        $state = $container['State'] ?? [];

        return [
            'service' => $service,
            'container' => ltrim((string) ($container['Name'] ?? ''), '/'),
            'state' => (string) ($state['Status'] ?? 'unknown'),
            'health' => (string) ($state['Health']['Status'] ?? 'n/a'),
            'uptime' => $this->formatUptime($state),
            'ports' => $this->formatPorts($container['NetworkSettings']['Ports'] ?? []),
            'exit_code' => isset($state['ExitCode']) ? (int) $state['ExitCode'] : null,
        ];
    }

    /**
     * Describe how long a container has been in its current state.
     *
     * @param  array<string, mixed>  $state
     */
    protected function formatUptime(array $state): string
    {
        $status = $state['Status'] ?? '';
        $reference = $status === 'running'
            ? ($state['StartedAt'] ?? null)
            : ($state['FinishedAt'] ?? null);

        // Docker reports the zero time for transitions that never happened.
        if (! is_string($reference) || $reference === '' || str_starts_with($reference, '0001-01-01')) {
            return '-';
        }

        try {
            return Carbon::parse($reference)->diffForHumans(null, CarbonInterface::DIFF_ABSOLUTE);
        } catch (Throwable $e) {
            return '-';
        }
    }

    /**
     * Render the published port bindings of a container.
     *
     * @param  array<string, mixed>  $ports
     */
    protected function formatPorts(array $ports): string
    {
        $formatted = [];

        foreach ($ports as $containerPort => $bindings) {
            if (empty($bindings) || ! is_array($bindings)) {
                continue;
            }

            foreach ($bindings as $binding) {
                $hostIp = $binding['HostIp'] ?? '';
                $prefix = ($hostIp === '' || $hostIp === '0.0.0.0' || $hostIp === '::') ? '' : $hostIp.':';

                $formatted[] = $prefix.($binding['HostPort'] ?? '').'->'.$containerPort;
            }
        }

        $formatted = array_unique($formatted);

        return $formatted === [] ? '-' : implode(', ', $formatted);
    }

    /**
     * Sample CPU, memory and I/O usage, keyed by container name.
     *
     * @param  array<int, string>  $ids
     * @return array<string, array{cpu: string, memory: string, memory_percent: string, network: string, block: string, pids: string}>
     */
    protected function getResourceUsage(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $command = sprintf(
            'docker stats --no-stream --format %s %s 2>/dev/null',
            escapeshellarg('{{json .}}'),
            implode(' ', array_map('escapeshellarg', $ids))
        );

        exec($command, $output, $returnCode);

        $stats = [];

        foreach ($output as $line) {
            $decoded = json_decode($line, true);

            if (! is_array($decoded) || ! isset($decoded['Name'])) {
                continue;
            }

            $stats[$decoded['Name']] = [
                'cpu' => (string) ($decoded['CPUPerc'] ?? '-'),
                'memory' => (string) ($decoded['MemUsage'] ?? '-'),
                'memory_percent' => (string) ($decoded['MemPerc'] ?? '-'),
                'network' => (string) ($decoded['NetIO'] ?? '-'),
                'block' => (string) ($decoded['BlockIO'] ?? '-'),
                'pids' => (string) ($decoded['PIDs'] ?? '-'),
            ];
        }

        return $stats;
    }

    /**
     * Get the names of the services that currently have a running container.
     *
     * @return array<int, string>
     */
    protected function getRunningServices(string $composeFile): array
    {
        $running = [];

        foreach ($this->getServiceStatuses($composeFile) as $row) {
            if ($row['state'] === 'running') {
                $running[] = $row['service'];
            }
        }

        return $running;
    }

    /**
     * Determine whether a service is running and past any healthcheck.
     *
     * @param  array{state: string, health: string}  $row
     */
    protected function serviceIsReady(array $row): bool
    {
        return $row['state'] === 'running'
            && ! in_array($row['health'], ['starting', 'unhealthy'], true);
    }

    /**
     * Block until every service is running and healthy, or the timeout expires.
     *
     * @param  int  $timeout  Maximum seconds to wait
     * @param  int  $interval  Seconds between polls
     * @return array{0: bool, 1: array<int, array<string, mixed>>} Readiness flag and the last status snapshot
     */
    protected function waitForHealthyServices(string $composeFile, int $timeout, int $interval = 3): array
    {
        $deadline = time() + $timeout;
        $statuses = [];

        do {
            $statuses = $this->getServiceStatuses($composeFile);

            $pending = array_filter($statuses, fn (array $row): bool => ! $this->serviceIsReady($row));

            if ($pending === []) {
                return [true, $statuses];
            }

            // A container that exited is never going to recover on its own.
            $failed = array_filter($statuses, fn (array $row): bool => in_array($row['state'], ['exited', 'dead'], true));

            if ($failed !== []) {
                return [false, $statuses];
            }

            if (time() + $interval > $deadline) {
                break;
            }

            sleep($interval);
        } while (time() < $deadline);

        return [false, $statuses];
    }

    /**
     * Run a command inside a service container without a TTY.
     *
     * @param  array<int, string>  $arguments  Command and its arguments, each escaped individually
     * @return array{0: int, 1: array<int, string>} Exit code and combined output
     */
    protected function runInContainer(string $composeFile, string $service, array $arguments): array
    {
        $command = sprintf(
            '%s -f %s exec -T %s %s 2>&1',
            $this->getDockerComposeCommand(),
            escapeshellarg($composeFile),
            escapeshellarg($service),
            implode(' ', array_map('escapeshellarg', $arguments))
        );

        exec($command, $output, $returnCode);

        return [$returnCode, $output];
    }
}
