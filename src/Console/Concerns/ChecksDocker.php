<?php

namespace Laradox\Console\Concerns;

trait ChecksDocker
{
    /**
     * Check if Docker is installed.
     *
     * @return bool
     */
    protected function checkDocker(): bool
    {
        exec('docker --version', $output, $returnCode);
        $output = $output ?? [];
        return $returnCode === 0;
    }

    /**
     * Check if Docker Compose is installed.
     *
     * @return bool
     */
    protected function checkDockerCompose(): bool
    {
        exec('docker compose version', $output, $returnCode);
        $output = $output ?? [];
        return $returnCode === 0;
    }

    /**
     * Handle missing Docker installation.
     *
     * @return int
     */
    protected function handleMissingDocker(): int
    {
        $this->newLine();
        $this->error('✗ Docker is not installed or not running.');
        $this->line('Docker is required to run Laradox containers.');
        $this->newLine();

        $os = $this->detectOperatingSystem();

        if ($os === 'linux') {
            if ($this->confirm('Would you like to install Docker automatically?', true)) {
                return $this->installDockerLinux();
            }
        } elseif ($os === 'macos') {
            $this->info('For macOS, please install Docker Desktop:');
            $this->line('1. Visit: https://www.docker.com/products/docker-desktop');
            $this->line('2. Download Docker Desktop for Mac');
            $this->line('3. Install and start Docker Desktop');
            $this->newLine();
            if ($this->confirm('Open the download page in your browser?', true)) {
                $this->openBrowser('https://www.docker.com/products/docker-desktop');
            }
        } elseif ($os === 'windows') {
            $this->info('For Windows, please install Docker Desktop:');
            $this->line('1. Visit: https://www.docker.com/products/docker-desktop');
            $this->line('2. Download Docker Desktop for Windows');
            $this->line('3. Install and start Docker Desktop');
            $this->line('4. Ensure WSL 2 is enabled (recommended)');
            $this->newLine();
            if ($this->confirm('Open the download page in your browser?', true)) {
                $this->openBrowser('https://www.docker.com/products/docker-desktop');
            }
        } else {
            $this->line('Please install Docker from: https://docs.docker.com/get-docker/');
        }

        $this->newLine();
        $this->line('After installation, run this command again.');
        $this->newLine();

        return 1; // FAILURE
    }

    /**
     * Install Docker on Linux.
     *
     * Detects the distribution and calls the appropriate installer.
     * Supports Ubuntu, Debian, Fedora, and CentOS/RHEL.
     *
     * Note: Requires systemd for service management. On WSL, containers,
     * or non-systemd distributions, manual installation may be needed.
     *
     * @return int
     */
    protected function installDockerLinux(): int
    {
        $this->info('Installing Docker on Linux...');
        $this->newLine();

        // Detect Linux distribution
        $hasApt = $this->commandAvailable('apt-get');
        $hasYum = $this->commandAvailable('yum');
        $hasDnf = $this->commandAvailable('dnf');

        if ($hasApt) {
            // Detect if Debian or Ubuntu
            $distro = $this->detectDebianDistribution();
            if ($distro === 'debian') {
                return $this->installDockerDebian();
            }
            return $this->installDockerUbuntu();
        } elseif ($hasDnf) {
            return $this->installDockerFedora();
        } elseif ($hasYum) {
            return $this->installDockerCentOS();
        } else {
            $this->error('Could not detect Linux distribution (apt, yum, or dnf).');
            $this->line('Please install Docker manually: https://docs.docker.com/engine/install/');
            return 1; // FAILURE
        }
    }

    /**
     * Install Docker on Ubuntu.
     *
     * @return int
     */
    protected function installDockerUbuntu(): int
    {
        $this->line('Installing Docker on Ubuntu...');
        $this->newLine();

        $commands = [
            // Remove old versions
            'sudo apt-get remove -y docker docker-engine docker.io containerd runc 2>/dev/null || true',
            // Update packages
            'sudo apt-get update',
            // Install prerequisites
            'sudo apt-get install -y ca-certificates curl',
            // Add Docker's official GPG key
            'sudo install -m 0755 -d /etc/apt/keyrings',
            'sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc',
            'sudo chmod a+r /etc/apt/keyrings/docker.asc',
            // Set up repository
            'echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null',
            // Install Docker Engine
            'sudo apt-get update',
            'sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin',
            // Add current user to docker group
            'sudo usermod -aG docker $USER',
        ];

        foreach ($commands as $command) {
            $this->line("Running: {$command}");
            passthru($command, $returnCode);
            if ($returnCode !== 0 && !str_contains($command, 'remove')) {
                $this->error('Installation failed.');
                $this->line('Please try installing manually: https://docs.docker.com/engine/install/ubuntu/');
                return 1; // FAILURE
            }
        }

        // Start Docker service (may fail on WSL/containers)
        $this->line('Starting Docker service...');
        exec('sudo systemctl start docker 2>/dev/null', $output, $returnCode);
        if ($returnCode === 0) {
            exec('sudo systemctl enable docker 2>/dev/null');
            $this->info('✓ Docker service started and enabled.');
        } else {
            $this->warn('⚠ Could not start Docker service (systemctl may not be available).');
            $this->line('On WSL or containers, you may need to start Docker manually.');
        }

        $this->newLine();
        $this->info('✓ Docker packages installed successfully!');
        $this->warn('⚠ You may need to log out and back in for group changes to take effect.');
        $this->newLine();

        return 0; // SUCCESS
    }

    /**
     * Install Docker on Debian.
     *
     * @return int
     */
    protected function installDockerDebian(): int
    {
        $this->line('Installing Docker on Debian...');
        $this->newLine();

        $commands = [
            // Remove old versions
            'sudo apt-get remove -y docker docker-engine docker.io containerd runc 2>/dev/null || true',
            // Update packages
            'sudo apt-get update',
            // Install prerequisites
            'sudo apt-get install -y ca-certificates curl',
            // Add Docker's official GPG key
            'sudo install -m 0755 -d /etc/apt/keyrings',
            'sudo curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc',
            'sudo chmod a+r /etc/apt/keyrings/docker.asc',
            // Set up repository
            'echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/debian $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null',
            // Install Docker Engine
            'sudo apt-get update',
            'sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin',
            // Add current user to docker group
            'sudo usermod -aG docker $USER',
        ];

        foreach ($commands as $command) {
            $this->line("Running: {$command}");
            passthru($command, $returnCode);
            if ($returnCode !== 0 && !str_contains($command, 'remove')) {
                $this->error('Installation failed.');
                $this->line('Please try installing manually: https://docs.docker.com/engine/install/debian/');
                return 1; // FAILURE
            }
        }

        // Start Docker service (may fail on WSL/containers)
        $this->line('Starting Docker service...');
        exec('sudo systemctl start docker 2>/dev/null', $output, $returnCode);
        if ($returnCode === 0) {
            exec('sudo systemctl enable docker 2>/dev/null');
            $this->info('✓ Docker service started and enabled.');
        } else {
            $this->warn('⚠ Could not start Docker service (systemctl may not be available).');
            $this->line('On WSL or containers, you may need to start Docker manually.');
        }

        $this->newLine();
        $this->info('✓ Docker packages installed successfully!');
        $this->warn('⚠ You may need to log out and back in for group changes to take effect.');
        $this->newLine();

        return 0; // SUCCESS
    }

    /**
     * Install Docker on Fedora.
     *
     * @return int
     */
    protected function installDockerFedora(): int
    {
        $this->line('Installing Docker on Fedora...');
        $this->newLine();

        $commands = [
            // Remove old versions
            'sudo dnf remove -y docker docker-client docker-client-latest docker-common docker-latest docker-latest-logrotate docker-logrotate docker-selinux docker-engine-selinux docker-engine 2>/dev/null || true',
            // Install dnf-plugins-core
            'sudo dnf -y install dnf-plugins-core',
            // Set up repository
            'sudo dnf config-manager --add-repo https://download.docker.com/linux/fedora/docker-ce.repo',
            // Install Docker Engine
            'sudo dnf install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin',
            // Add current user to docker group
            'sudo usermod -aG docker $USER',
        ];

        foreach ($commands as $command) {
            $this->line("Running: {$command}");
            passthru($command, $returnCode);
            if ($returnCode !== 0 && !str_contains($command, 'remove')) {
                $this->error('Installation failed.');
                $this->line('Please try installing manually: https://docs.docker.com/engine/install/fedora/');
                return 1; // FAILURE
            }
        }

        // Start Docker service (may fail on containers)
        $this->line('Starting Docker service...');
        exec('sudo systemctl start docker 2>/dev/null', $output, $returnCode);
        if ($returnCode === 0) {
            exec('sudo systemctl enable docker 2>/dev/null');
            $this->info('✓ Docker service started and enabled.');
        } else {
            $this->warn('⚠ Could not start Docker service (systemctl may not be available).');
        }

        $this->newLine();
        $this->info('✓ Docker packages installed successfully!');
        $this->warn('⚠ You may need to log out and back in for group changes to take effect.');
        $this->newLine();

        return 0; // SUCCESS
    }

    /**
     * Install Docker on CentOS/RHEL.
     *
     * @return int
     */
    protected function installDockerCentOS(): int
    {
        $this->line('Installing Docker on CentOS/RHEL...');
        $this->newLine();

        $commands = [
            // Remove old versions
            'sudo yum remove -y docker docker-client docker-client-latest docker-common docker-latest docker-latest-logrotate docker-logrotate docker-engine 2>/dev/null || true',
            // Install yum-utils
            'sudo yum install -y yum-utils',
            // Set up repository
            'sudo yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo',
            // Install Docker Engine
            'sudo yum install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin',
            // Add current user to docker group
            'sudo usermod -aG docker $USER',
        ];

        foreach ($commands as $command) {
            $this->line("Running: {$command}");
            passthru($command, $returnCode);
            if ($returnCode !== 0 && !str_contains($command, 'remove')) {
                $this->error('Installation failed.');
                $this->line('Please try installing manually: https://docs.docker.com/engine/install/centos/');
                return 1; // FAILURE
            }
        }

        // Start Docker service (may fail on containers)
        $this->line('Starting Docker service...');
        exec('sudo systemctl start docker 2>/dev/null', $output, $returnCode);
        if ($returnCode === 0) {
            exec('sudo systemctl enable docker 2>/dev/null');
            $this->info('✓ Docker service started and enabled.');
        } else {
            $this->warn('⚠ Could not start Docker service (systemctl may not be available).');
        }

        $this->newLine();
        $this->info('✓ Docker packages installed successfully!');
        $this->warn('⚠ You may need to log out and back in for group changes to take effect.');
        $this->newLine();

        return 0; // SUCCESS
    }

    /**
     * Detect if the system is Debian or Ubuntu.
     *
     * @return string 'debian', 'ubuntu', or 'unknown'
     */
    protected function detectDebianDistribution(): string
    {
        // Check /etc/os-release for ID field
        if (file_exists('/etc/os-release')) {
            $osRelease = file_get_contents('/etc/os-release');
            if (preg_match('/^ID=(.*)$/m', $osRelease, $matches)) {
                $id = trim($matches[1], '"');
                if ($id === 'debian') {
                    return 'debian';
                } elseif ($id === 'ubuntu') {
                    return 'ubuntu';
                }
            }
        }

        // Fallback: check for lsb_release command
        exec('lsb_release -is 2>/dev/null', $output, $returnCode);
        if ($returnCode === 0 && !empty($output)) {
            $distro = strtolower(trim($output[0]));
            if ($distro === 'debian') {
                return 'debian';
            } elseif ($distro === 'ubuntu') {
                return 'ubuntu';
            }
        }

        // Default to Ubuntu if cannot determine
        return 'ubuntu';
    }

    /**
     * Detect the operating system.
     *
     * @return string 'linux', 'macos', 'windows', or 'unknown'
     */
    protected function detectOperatingSystem(): string
    {
        $os = strtolower(PHP_OS);

        if (stripos($os, 'linux') !== false) {
            return 'linux';
        } elseif (stripos($os, 'darwin') !== false) {
            return 'macos';
        } elseif (stripos($os, 'win') !== false) {
            return 'windows';
        }

        return 'unknown';
    }

    /**
     * Check if a command is available.
     *
     * @param string $command
     * @return bool
     */
    protected function commandAvailable(string $command): bool
    {
        exec('which ' . escapeshellarg($command), $output, $returnCode);
        unset($output);
        return $returnCode === 0;
    }

    /**
     * Open a URL in the default browser.
     *
     * @param string $url
     * @return void
     */
    protected function openBrowser(string $url): void
    {
        $os = $this->detectOperatingSystem();

        if ($os === 'macos') {
            exec("open " . escapeshellarg($url));
        } elseif ($os === 'linux') {
            exec("xdg-open " . escapeshellarg($url) . " 2>/dev/null &");
        } elseif ($os === 'windows') {
            // Use an empty window title to ensure the URL is treated as the target
            exec('start "" ' . escapeshellarg($url));
        }
    }
}
