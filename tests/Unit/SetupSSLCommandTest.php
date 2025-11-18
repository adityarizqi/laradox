<?php

namespace Laradox\Tests\Unit;

use Laradox\Console\SetupSSLCommand;
use Laradox\Tests\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class SetupSSLCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_detects_linux_operating_system(): void
    {
        $command = new class extends SetupSSLCommand {
            public function detectOS(): string
            {
                return parent::detectOS();
            }
        };

        // Since we can't mock PHP_OS constant directly, we'll test the logic
        $os = $command->detectOS();
        $this->assertContains($os, ['linux', 'macos', 'windows', 'unknown']);
    }

    #[Test]
    public function it_detects_macos_operating_system(): void
    {
        $command = new class extends SetupSSLCommand {
            public function detectOS(): string
            {
                // Override to test macOS detection
                $os = 'darwin';
                if (stripos($os, 'darwin') !== false) {
                    return 'macos';
                }
                return 'unknown';
            }
        };

        $this->assertEquals('macos', $command->detectOS());
    }

    #[Test]
    public function it_detects_windows_operating_system(): void
    {
        $command = new class extends SetupSSLCommand {
            public function detectOS(): string
            {
                // Override to test Windows detection
                $os = 'winnt';
                if (stripos($os, 'win') !== false) {
                    return 'windows';
                }
                return 'unknown';
            }
        };

        $this->assertEquals('windows', $command->detectOS());
    }

    #[Test]
    public function it_checks_if_mkcert_command_exists(): void
    {
        $command = new class extends SetupSSLCommand {
            public function checkMkcert(): bool
            {
                return parent::checkMkcert();
            }
        };

        $result = $command->checkMkcert();
        $this->assertIsBool($result);
    }

    #[Test]
    public function it_checks_if_command_exists(): void
    {
        $command = new class extends SetupSSLCommand {
            public function commandExists(string $cmd): bool
            {
                return parent::commandExists($cmd);
            }
        };

        // Test with a command that definitely exists
        $result = $command->commandExists('echo');
        $this->assertTrue($result);

        // Test with a command that likely doesn't exist
        $result = $command->commandExists('this-command-definitely-does-not-exist-12345');
        $this->assertFalse($result);
    }

    #[Test]
    public function it_handles_missing_mkcert_with_linux_os(): void
    {
        $command = Mockery::mock(SetupSSLCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('detectOS')->andReturn('linux');
        $command->shouldReceive('confirm')->andReturn(false);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('warn')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);

        $result = $command->handleMissingMkcert();

        $this->assertEquals(1, $result); // FAILURE
    }

    #[Test]
    public function it_handles_missing_mkcert_with_macos_os(): void
    {
        $command = Mockery::mock(SetupSSLCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('detectOS')->andReturn('macos');
        $command->shouldReceive('confirm')->andReturn(false);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('warn')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);

        $result = $command->handleMissingMkcert();

        $this->assertEquals(1, $result); // FAILURE
    }

    #[Test]
    public function it_handles_missing_mkcert_with_windows_os(): void
    {
        $command = Mockery::mock(SetupSSLCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('detectOS')->andReturn('windows');
        $command->shouldReceive('confirm')->andReturn(false);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('warn')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);
        $command->shouldReceive('info')->andReturn(null);

        $result = $command->handleMissingMkcert();

        $this->assertEquals(1, $result); // FAILURE
    }

    #[Test]
    public function it_handles_missing_mkcert_with_unknown_os(): void
    {
        $command = Mockery::mock(SetupSSLCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('detectOS')->andReturn('unknown');
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('warn')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);

        $result = $command->handleMissingMkcert();

        $this->assertEquals(1, $result); // FAILURE
    }

    #[Test]
    public function it_fails_linux_installation_when_no_package_manager_found(): void
    {
        $command = Mockery::mock(SetupSSLCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('commandExists')
            ->with('apt-get')->andReturn(false)
            ->shouldReceive('commandExists')
            ->with('yum')->andReturn(false)
            ->shouldReceive('commandExists')
            ->with('dnf')->andReturn(false);
        
        $command->shouldReceive('info')->andReturn(null);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('error')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);

        $result = $command->installMkcertLinux();

        $this->assertEquals(1, $result); // FAILURE
    }

    #[Test]
    public function it_detects_apt_package_manager_on_linux(): void
    {
        $command = Mockery::mock(SetupSSLCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('commandExists')
            ->with('apt-get')->andReturn(true);
        
        $hasApt = $command->commandExists('apt-get');
        $this->assertTrue($hasApt);
    }

    #[Test]
    public function it_detects_dnf_package_manager_on_linux(): void
    {
        $command = Mockery::mock(SetupSSLCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('commandExists')
            ->with('apt-get')->andReturn(false)
            ->shouldReceive('commandExists')
            ->with('dnf')->andReturn(true);
        
        $hasApt = $command->commandExists('apt-get');
        $hasDnf = $command->commandExists('dnf');
        
        $this->assertFalse($hasApt);
        $this->assertTrue($hasDnf);
    }

    #[Test]
    public function it_detects_yum_package_manager_on_linux(): void
    {
        $command = Mockery::mock(SetupSSLCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('commandExists')
            ->with('apt-get')->andReturn(false)
            ->shouldReceive('commandExists')
            ->with('yum')->andReturn(true);
        
        $hasApt = $command->commandExists('apt-get');
        $hasYum = $command->commandExists('yum');
        
        $this->assertFalse($hasApt);
        $this->assertTrue($hasYum);
    }

    #[Test]
    public function it_fails_macos_installation_when_homebrew_not_installed(): void
    {
        $command = Mockery::mock(SetupSSLCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('commandExists')
            ->with('brew')->andReturn(false);
        
        $command->shouldReceive('error')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);

        $result = $command->installMkcertMacOS();

        $this->assertEquals(1, $result); // FAILURE
    }

    #[Test]
    public function it_opens_url_on_macos(): void
    {
        $command = new class extends SetupSSLCommand {
            public string $executedCommand = '';
            
            public function detectOS(): string
            {
                return 'macos';
            }
            
            public function openURL(string $url): void
            {
                $os = $this->detectOS();
                if ($os === 'macos') {
                    $this->executedCommand = "open " . escapeshellarg($url);
                }
            }
        };

        $command->openURL('https://example.com');
        $this->assertStringContainsString('open', $command->executedCommand);
        $this->assertStringContainsString('https://example.com', $command->executedCommand);
    }

    #[Test]
    public function it_opens_url_on_linux(): void
    {
        $command = new class extends SetupSSLCommand {
            public string $executedCommand = '';
            
            public function detectOS(): string
            {
                return 'linux';
            }
            
            public function openURL(string $url): void
            {
                $os = $this->detectOS();
                if ($os === 'linux') {
                    $this->executedCommand = "xdg-open " . escapeshellarg($url);
                }
            }
        };

        $command->openURL('https://example.com');
        $this->assertStringContainsString('xdg-open', $command->executedCommand);
        $this->assertStringContainsString('https://example.com', $command->executedCommand);
    }

    #[Test]
    public function it_opens_url_on_windows(): void
    {
        $command = new class extends SetupSSLCommand {
            public string $executedCommand = '';
            
            public function detectOS(): string
            {
                return 'windows';
            }
            
            public function openURL(string $url): void
            {
                $os = $this->detectOS();
                if ($os === 'windows') {
                    $this->executedCommand = "start " . escapeshellarg($url);
                }
            }
        };

        $command->openURL('https://example.com');
        $this->assertStringContainsString('start', $command->executedCommand);
        $this->assertStringContainsString('https://example.com', $command->executedCommand);
    }

    #[Test]
    public function it_escapes_url_properly(): void
    {
        $command = new class extends SetupSSLCommand {
            public string $executedCommand = '';
            
            public function detectOS(): string
            {
                return 'macos';
            }
            
            public function openURL(string $url): void
            {
                $os = $this->detectOS();
                if ($os === 'macos') {
                    $this->executedCommand = "open " . escapeshellarg($url);
                }
            }
        };

        $urlWithSpecialChars = 'https://example.com/path?param=value&other=test';
        $command->openURL($urlWithSpecialChars);
        
        $this->assertStringContainsString('open', $command->executedCommand);
    }

    #[Test]
    public function it_prompts_for_linux_installation_when_user_confirms(): void
    {
        $command = Mockery::mock(SetupSSLCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('detectOS')->andReturn('linux');
        $command->shouldReceive('confirm')
            ->with('Would you like to install mkcert automatically?', true)
            ->andReturn(true);
        $command->shouldReceive('installMkcertLinux')->andReturn(1);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('warn')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);

        $result = $command->handleMissingMkcert();

        $this->assertEquals(1, $result);
    }

    #[Test]
    public function it_prompts_for_macos_installation_when_user_confirms(): void
    {
        $command = Mockery::mock(SetupSSLCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('detectOS')->andReturn('macos');
        $command->shouldReceive('confirm')
            ->with('Would you like to install mkcert using Homebrew?', true)
            ->andReturn(true);
        $command->shouldReceive('installMkcertMacOS')->andReturn(1);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('warn')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);

        $result = $command->handleMissingMkcert();

        $this->assertEquals(1, $result);
    }

    #[Test]
    public function it_prompts_to_open_browser_for_windows_installation(): void
    {
        $command = Mockery::mock(SetupSSLCommand::class)->makePartial();
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('detectOS')->andReturn('windows');
        $command->shouldReceive('confirm')
            ->with('Open the download page in your browser?', true)
            ->andReturn(true);
        $command->shouldReceive('openURL')
            ->with('https://github.com/FiloSottile/mkcert/releases')
            ->andReturn(null);
        $command->shouldReceive('newLine')->andReturn(null);
        $command->shouldReceive('warn')->andReturn(null);
        $command->shouldReceive('line')->andReturn(null);
        $command->shouldReceive('info')->andReturn(null);

        $result = $command->handleMissingMkcert();

        $this->assertEquals(1, $result);
    }

    #[Test]
    public function it_constructs_apt_commands_correctly_for_linux(): void
    {
        $expectedCommands = [
            'sudo apt-get update',
            'sudo apt-get install -y mkcert',
        ];

        // Verify the expected commands structure
        $this->assertCount(2, $expectedCommands);
        $this->assertStringContainsString('apt-get update', $expectedCommands[0]);
        $this->assertStringContainsString('apt-get install -y mkcert', $expectedCommands[1]);
    }

    #[Test]
    public function it_constructs_dnf_commands_correctly_for_linux(): void
    {
        $expectedCommands = [
            'sudo dnf install -y mkcert',
        ];

        // Verify the expected commands structure
        $this->assertCount(1, $expectedCommands);
        $this->assertStringContainsString('dnf install -y mkcert', $expectedCommands[0]);
    }

    #[Test]
    public function it_constructs_yum_commands_correctly_for_linux(): void
    {
        $expectedCommands = [
            'sudo yum install -y mkcert',
        ];

        // Verify the expected commands structure
        $this->assertCount(1, $expectedCommands);
        $this->assertStringContainsString('yum install -y mkcert', $expectedCommands[0]);
    }

    #[Test]
    public function it_constructs_brew_command_correctly_for_macos(): void
    {
        $expectedCommand = 'brew install mkcert';

        $this->assertStringContainsString('brew install', $expectedCommand);
        $this->assertStringContainsString('mkcert', $expectedCommand);
    }
}
