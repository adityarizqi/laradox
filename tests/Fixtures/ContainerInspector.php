<?php

namespace Laradox\Tests\Fixtures;

use Laradox\Console\Concerns\ChecksDocker;
use Laradox\Console\Concerns\InspectsContainers;

/**
 * Exposes the protected helpers of InspectsContainers so the pure formatting
 * and readiness logic can be tested without a Docker daemon.
 */
class ContainerInspector
{
    use ChecksDocker;
    use InspectsContainers;

    /**
     * @param  array<string, mixed>  $container
     * @return array<string, mixed>|null
     */
    public function normalize(array $container): ?array
    {
        return $this->normalizeContainer($container);
    }

    /**
     * @param  array<string, mixed>  $ports
     */
    public function ports(array $ports): string
    {
        return $this->formatPorts($ports);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function uptime(array $state): string
    {
        return $this->formatUptime($state);
    }

    /**
     * @param  array{state: string, health: string}  $row
     */
    public function ready(array $row): bool
    {
        return $this->serviceIsReady($row);
    }
}
