<?php

namespace Laradox\Tests\Unit;

use Laradox\Tests\Fixtures\ContainerInspector;
use Laradox\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class InspectsContainersTest extends TestCase
{
    private ContainerInspector $inspector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inspector = new ContainerInspector;
    }

    #[Test]
    public function it_normalizes_an_inspected_container(): void
    {
        $row = $this->inspector->normalize([
            'Name' => '/laradox-php-1',
            'Config' => ['Labels' => ['com.docker.compose.service' => 'php']],
            'State' => [
                'Status' => 'running',
                'ExitCode' => 0,
                'StartedAt' => '2026-01-01T00:00:00.000000000Z',
                'Health' => ['Status' => 'healthy'],
            ],
            'NetworkSettings' => ['Ports' => []],
        ]);

        $this->assertNotNull($row);
        $this->assertEquals('php', $row['service']);
        $this->assertEquals('laradox-php-1', $row['container']);
        $this->assertEquals('running', $row['state']);
        $this->assertEquals('healthy', $row['health']);
        $this->assertEquals(0, $row['exit_code']);
    }

    #[Test]
    public function it_reports_missing_healthchecks_as_not_applicable(): void
    {
        $row = $this->inspector->normalize([
            'Name' => '/laradox-node-1',
            'Config' => ['Labels' => ['com.docker.compose.service' => 'node']],
            'State' => ['Status' => 'running'],
        ]);

        $this->assertNotNull($row);
        $this->assertEquals('n/a', $row['health']);
    }

    #[Test]
    public function it_ignores_containers_without_a_compose_service_label(): void
    {
        $row = $this->inspector->normalize([
            'Name' => '/some-other-container',
            'Config' => ['Labels' => []],
            'State' => ['Status' => 'running'],
        ]);

        $this->assertNull($row);
    }

    #[Test]
    public function it_formats_published_ports(): void
    {
        $ports = $this->inspector->ports([
            '80/tcp' => [['HostIp' => '0.0.0.0', 'HostPort' => '80']],
            '443/tcp' => [['HostIp' => '127.0.0.1', 'HostPort' => '8443']],
        ]);

        $this->assertStringContainsString('80->80/tcp', $ports);
        $this->assertStringContainsString('127.0.0.1:8443->443/tcp', $ports);
    }

    #[Test]
    public function it_formats_unpublished_ports_as_a_dash(): void
    {
        $this->assertEquals('-', $this->inspector->ports(['8080/tcp' => null]));
        $this->assertEquals('-', $this->inspector->ports([]));
    }

    #[Test]
    public function it_returns_a_dash_for_the_docker_zero_time(): void
    {
        $this->assertEquals('-', $this->inspector->uptime([
            'Status' => 'created',
            'FinishedAt' => '0001-01-01T00:00:00Z',
        ]));
    }

    #[Test]
    public function it_describes_how_long_a_container_has_been_running(): void
    {
        $uptime = $this->inspector->uptime([
            'Status' => 'running',
            'StartedAt' => now()->subMinutes(5)->toIso8601String(),
        ]);

        $this->assertStringContainsString('minute', $uptime);
        $this->assertStringNotContainsString('ago', $uptime);
    }

    #[Test]
    public function it_treats_running_and_healthy_services_as_ready(): void
    {
        $this->assertTrue($this->inspector->ready(['state' => 'running', 'health' => 'healthy']));
        $this->assertTrue($this->inspector->ready(['state' => 'running', 'health' => 'n/a']));
    }

    #[Test]
    public function it_does_not_treat_starting_or_stopped_services_as_ready(): void
    {
        $this->assertFalse($this->inspector->ready(['state' => 'running', 'health' => 'starting']));
        $this->assertFalse($this->inspector->ready(['state' => 'running', 'health' => 'unhealthy']));
        $this->assertFalse($this->inspector->ready(['state' => 'exited', 'health' => 'n/a']));
        $this->assertFalse($this->inspector->ready(['state' => 'not created', 'health' => 'n/a']));
    }
}
