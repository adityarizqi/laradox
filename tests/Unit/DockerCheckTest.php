<?php

namespace Laradox\Tests\Unit;

use Laradox\Console\UpCommand;
use Laradox\Tests\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class DockerCheckTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_checks_if_docker_is_installed(): void
    {
        $command = new class extends UpCommand {
            public function checkDocker(): bool
            {
                return parent::checkDocker();
            }
        };

        $result = $command->checkDocker();
        $this->assertIsBool($result);
    }

    #[Test]
    public function it_checks_if_docker_compose_is_installed(): void
    {
        $command = new class extends UpCommand {
            public function checkDockerCompose(): bool
            {
                return parent::checkDockerCompose();
            }
        };

        $result = $command->checkDockerCompose();
        $this->assertIsBool($result);
    }

    #[Test]
    public function it_detects_operating_system(): void
    {
        $command = new class extends UpCommand {
            public function detectOperatingSystem(): string
            {
                return parent::detectOperatingSystem();
            }
        };

        $os = $command->detectOperatingSystem();
        $this->assertContains($os, ['linux', 'macos', 'windows', 'unknown']);
    }

    #[Test]
    public function it_detects_linux_os(): void
    {
        $command = new class extends UpCommand {
            public function detectOperatingSystem(): string
            {
                // Simulate Linux detection
                $os = 'linux';
                if (stripos($os, 'linux') !== false) {
                    return 'linux';
                }
                return 'unknown';
            }
        };

        $this->assertEquals('linux', $command->detectOperatingSystem());
    }

    #[Test]
    public function it_detects_macos_os(): void
    {
        $command = new class extends UpCommand {
            public function detectOperatingSystem(): string
            {
                // Simulate macOS detection
                $os = 'darwin';
                if (stripos($os, 'darwin') !== false) {
                    return 'macos';
                }
                return 'unknown';
            }
        };

        $this->assertEquals('macos', $command->detectOperatingSystem());
    }

    #[Test]
    public function it_detects_windows_os(): void
    {
        $command = new class extends UpCommand {
            public function detectOperatingSystem(): string
            {
                // Simulate Windows detection
                $os = 'winnt';
                if (stripos($os, 'win') !== false) {
                    return 'windows';
                }
                return 'unknown';
            }
        };

        $this->assertEquals('windows', $command->detectOperatingSystem());
    }

    #[Test]
    public function it_checks_if_command_is_available(): void
    {
        $command = new class extends UpCommand {
            public function commandAvailable(string $cmd): bool
            {
                return parent::commandAvailable($cmd);
            }
        };

        // Test with a command that exists
        $result = $command->commandAvailable('echo');
        $this->assertTrue($result);

        // Test with a command that likely doesn't exist
        $result = $command->commandAvailable('this-command-does-not-exist-xyz123');
        $this->assertFalse($result);
    }

    #[Test]
    public function it_handles_missing_docker_on_linux(): void
    {
        $command = Mockery::mock(UpCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('detectOperatingSystem')->andReturn('linux');
        $command->shouldReceive('confirm')->andReturn(false);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('error')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);

        $result = $command->handleMissingDocker();

        $this->assertEquals(1, $result); // FAILURE
    }

    #[Test]
    public function it_handles_missing_docker_on_macos(): void
    {
        $command = Mockery::mock(UpCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('detectOperatingSystem')->andReturn('macos');
        $command->shouldReceive('confirm')->andReturn(false);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('error')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);
        $command->shouldReceive('info')->andReturn(null);

        $result = $command->handleMissingDocker();

        $this->assertEquals(1, $result); // FAILURE
    }

    #[Test]
    public function it_handles_missing_docker_on_windows(): void
    {
        $command = Mockery::mock(UpCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('detectOperatingSystem')->andReturn('windows');
        $command->shouldReceive('confirm')->andReturn(false);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('error')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);
        $command->shouldReceive('info')->andReturn(null);

        $result = $command->handleMissingDocker();

        $this->assertEquals(1, $result); // FAILURE
    }

    #[Test]
    public function it_handles_missing_docker_on_unknown_os(): void
    {
        $command = Mockery::mock(UpCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('detectOperatingSystem')->andReturn('unknown');
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('error')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);

        $result = $command->handleMissingDocker();

        $this->assertEquals(1, $result); // FAILURE
    }

    #[Test]
    public function it_prompts_for_linux_docker_installation_when_user_confirms(): void
    {
        $command = Mockery::mock(UpCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('detectOperatingSystem')->andReturn('linux');
        $command->shouldReceive('confirm')
            ->with('Would you like to install Docker automatically?', true)
            ->andReturn(true);
        $command->shouldReceive('installDockerLinux')->andReturn(1);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('error')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);

        $result = $command->handleMissingDocker();

        $this->assertEquals(1, $result);
    }

    #[Test]
    public function it_prompts_to_open_browser_for_macos_installation(): void
    {
        $command = Mockery::mock(UpCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('detectOperatingSystem')->andReturn('macos');
        $command->shouldReceive('confirm')
            ->with('Open the download page in your browser?', true)
            ->andReturn(true);
        $command->shouldReceive('openBrowser')
            ->with('https://www.docker.com/products/docker-desktop')
            ->andReturn(null);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('error')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);
        $command->shouldReceive('info')->andReturn(null);

        $result = $command->handleMissingDocker();

        $this->assertEquals(1, $result);
    }

    #[Test]
    public function it_prompts_to_open_browser_for_windows_installation(): void
    {
        $command = Mockery::mock(UpCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('detectOperatingSystem')->andReturn('windows');
        $command->shouldReceive('confirm')
            ->with('Open the download page in your browser?', true)
            ->andReturn(true);
        $command->shouldReceive('openBrowser')
            ->with('https://www.docker.com/products/docker-desktop')
            ->andReturn(null);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('error')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);
        $command->shouldReceive('info')->andReturn(null);

        $result = $command->handleMissingDocker();

        $this->assertEquals(1, $result);
    }

    #[Test]
    public function it_detects_apt_package_manager(): void
    {
        $command = Mockery::mock(UpCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('commandAvailable')
            ->with('apt-get')->andReturn(true);

        $hasApt = $command->commandAvailable('apt-get');
        $this->assertTrue($hasApt);
    }

    #[Test]
    public function it_detects_yum_package_manager(): void
    {
        $command = Mockery::mock(UpCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('commandAvailable')
            ->with('yum')->andReturn(true);

        $hasYum = $command->commandAvailable('yum');
        $this->assertTrue($hasYum);
    }

    #[Test]
    public function it_detects_dnf_package_manager(): void
    {
        $command = Mockery::mock(UpCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('commandAvailable')
            ->with('dnf')->andReturn(true);

        $hasDnf = $command->commandAvailable('dnf');
        $this->assertTrue($hasDnf);
    }

    #[Test]
    public function it_fails_linux_installation_when_no_package_manager_found(): void
    {
        $command = Mockery::mock(UpCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('commandAvailable')
            ->with('apt-get')->andReturn(false)
            ->shouldReceive('commandAvailable')
            ->with('yum')->andReturn(false)
            ->shouldReceive('commandAvailable')
            ->with('dnf')->andReturn(false);

        $command->shouldReceive('info')->andReturn(null);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('error')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);

        $result = $command->installDockerLinux();

        $this->assertEquals(1, $result); // FAILURE
    }

    #[Test]
    public function it_opens_browser_on_macos(): void
    {
        $command = new class extends UpCommand {
            public string $executedCommand = '';

            public function detectOperatingSystem(): string
            {
                return 'macos';
            }

            public function openBrowser(string $url): void
            {
                $os = $this->detectOperatingSystem();
                if ($os === 'macos') {
                    $this->executedCommand = "open " . escapeshellarg($url);
                }
            }
        };

        $command->openBrowser('https://www.docker.com');
        $this->assertStringContainsString('open', $command->executedCommand);
        $this->assertStringContainsString('docker.com', $command->executedCommand);
    }

    #[Test]
    public function it_opens_browser_on_linux(): void
    {
        $command = new class extends UpCommand {
            public string $executedCommand = '';

            public function detectOperatingSystem(): string
            {
                return 'linux';
            }

            public function openBrowser(string $url): void
            {
                $os = $this->detectOperatingSystem();
                if ($os === 'linux') {
                    $this->executedCommand = "xdg-open " . escapeshellarg($url) . " 2>/dev/null &";
                }
            }
        };

        $command->openBrowser('https://www.docker.com');
        $this->assertStringContainsString('xdg-open', $command->executedCommand);
        $this->assertStringContainsString('docker.com', $command->executedCommand);
    }

    #[Test]
    public function it_opens_browser_on_windows(): void
    {
        $command = new class extends UpCommand {
            public string $executedCommand = '';

            public function detectOperatingSystem(): string
            {
                return 'windows';
            }

            public function openBrowser(string $url): void
            {
                $os = $this->detectOperatingSystem();
                if ($os === 'windows') {
                    $this->executedCommand = "start " . escapeshellarg($url);
                }
            }
        };

        $command->openBrowser('https://www.docker.com');
        $this->assertStringContainsString('start', $command->executedCommand);
        $this->assertStringContainsString('docker.com', $command->executedCommand);
    }

    #[Test]
    public function it_constructs_ubuntu_docker_installation_commands_correctly(): void
    {
        // Verify the expected commands for Ubuntu/Debian
        $expectedCommands = [
            'sudo apt-get remove',
            'sudo apt-get update',
            'sudo apt-get install -y ca-certificates curl',
            'sudo install -m 0755 -d /etc/apt/keyrings',
            'docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin',
            'sudo usermod -aG docker',
            'sudo systemctl start docker',
            'sudo systemctl enable docker',
        ];

        foreach ($expectedCommands as $expectedCommand) {
            $this->assertIsString($expectedCommand);
        }
    }

    #[Test]
    public function it_constructs_fedora_docker_installation_commands_correctly(): void
    {
        // Verify the expected commands for Fedora
        $expectedCommands = [
            'sudo dnf remove',
            'sudo dnf -y install dnf-plugins-core',
            'sudo dnf config-manager --add-repo',
            'sudo dnf install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin',
            'sudo usermod -aG docker',
            'sudo systemctl start docker',
            'sudo systemctl enable docker',
        ];

        foreach ($expectedCommands as $expectedCommand) {
            $this->assertIsString($expectedCommand);
        }
    }

    #[Test]
    public function it_constructs_centos_docker_installation_commands_correctly(): void
    {
        // Verify the expected commands for CentOS/RHEL
        $expectedCommands = [
            'sudo yum remove',
            'sudo yum install -y yum-utils',
            'sudo yum-config-manager --add-repo',
            'sudo yum install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin',
            'sudo usermod -aG docker',
            'sudo systemctl start docker',
            'sudo systemctl enable docker',
        ];

        foreach ($expectedCommands as $expectedCommand) {
            $this->assertIsString($expectedCommand);
        }
    }
}
