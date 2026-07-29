<?php

namespace Laradox\Console;

use Closure;
use Illuminate\Console\Command;
use Laradox\Console\Concerns\ChecksDocker;
use Laradox\Console\Concerns\InspectsContainers;

class DeployCommand extends Command
{
    use ChecksDocker;
    use InspectsContainers;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradox:deploy
                            {--f|file= : Custom Docker Compose file path}
                            {--environment=production : Environment (development|production)}
                            {--service=php : Service the Composer and Artisan steps run in}
                            {--node-service=node : Service the asset build runs in}
                            {--no-pull : Skip pulling the latest code with git}
                            {--no-build : Skip rebuilding the Docker images}
                            {--no-composer : Skip installing the PHP dependencies}
                            {--no-assets : Skip the front-end asset build}
                            {--no-migrate : Skip database migrations}
                            {--no-optimize : Skip the cache warm-up}
                            {--maintenance : Put the application in maintenance mode during the deploy}
                            {--timeout=120 : Seconds to wait for the services to become healthy}
                            {--dry-run : Print the deployment plan without running it}
                            {--force : Do not ask for confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy the application: pull, build, migrate, optimize and health-check';

    /**
     * Whether this run put the application into maintenance mode.
     */
    protected bool $maintenanceEngaged = false;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check if Docker is installed
        if (! $this->checkDocker()) {
            return $this->handleMissingDocker();
        }

        // Check if Docker Compose is available
        if (! $this->checkDockerCompose()) {
            $this->newLine();
            $this->error('✗ Docker Compose is not available.');
            $this->line('Please ensure Docker Compose is installed and running.');
            $this->line('Visit: https://docs.docker.com/compose/install/');
            $this->newLine();

            return self::FAILURE;
        }

        $env = $this->option('environment');
        $customFile = $this->option('file');

        // Determine compose file path
        $composeFile = $this->resolveComposeFile($env, $customFile);
        if ($composeFile === false) {
            return self::FAILURE;
        }

        if (! file_exists($composeFile)) {
            $this->error("Docker Compose file not found: {$composeFile}");

            return self::FAILURE;
        }

        $timeout = $this->option('timeout');
        if (! is_numeric($timeout) || (int) $timeout < 1) {
            $this->error('The --timeout option must be a positive integer.');

            return self::FAILURE;
        }

        // The nginx service bind-mounts app.conf; without it Docker would
        // silently create a directory in its place and nginx would not start.
        if (! $this->checkNginxConfig()) {
            return self::FAILURE;
        }

        $plan = $this->buildPlan($composeFile, $env, (int) $timeout);

        $this->printPlan($plan, $composeFile, $env);

        if ($this->option('dry-run')) {
            $this->comment('Dry run — nothing was executed.');
            $this->newLine();

            return self::SUCCESS;
        }

        $declined = $this->confirmDeployment($env, $composeFile);

        if ($declined !== null) {
            return $declined;
        }

        return $this->executePlan($plan, $composeFile);
    }

    /**
     * Verify the generated nginx server block is in place.
     */
    protected function checkNginxConfig(): bool
    {
        $confDirectory = base_path('docker/nginx/conf.d');

        // Custom setups may not ship the Laradox nginx layout at all.
        if (! is_dir($confDirectory)) {
            return true;
        }

        if (file_exists($confDirectory.'/app.conf')) {
            return true;
        }

        $this->newLine();
        $this->error('✗ nginx configuration has not been generated yet.');
        $this->line('docker/nginx/conf.d/app.conf is missing, so nginx would fail to start.');
        $this->newLine();
        $this->comment('Generate it once with:');
        $this->line('  php artisan laradox:up --environment='.$this->option('environment').' --detach');
        $this->newLine();

        return false;
    }

    /**
     * Build the ordered deployment plan.
     *
     * @return array<int, array{label: string, detail: string, run: Closure(): bool}>
     */
    protected function buildPlan(string $composeFile, string $env, int $timeout): array
    {
        $service = $this->option('service');
        $nodeService = $this->option('node-service');
        $plan = [];

        if (! $this->option('no-pull')) {
            $plan[] = [
                'label' => 'Pull latest code',
                'detail' => 'git pull --ff-only',
                'run' => fn (): bool => $this->pullCode(),
            ];
        }

        if ($this->option('maintenance')) {
            $plan[] = [
                'label' => 'Enable maintenance mode',
                'detail' => "artisan down (in {$service})",
                'run' => fn (): bool => $this->enableMaintenanceMode($composeFile, $service),
            ];
        }

        if (! $this->option('no-build')) {
            $plan[] = [
                'label' => 'Build images',
                'detail' => 'docker compose build',
                'run' => fn (): bool => $this->composePassthru($composeFile, 'build'),
            ];
        }

        $plan[] = [
            'label' => 'Start containers',
            'detail' => 'docker compose up -d --remove-orphans',
            'run' => fn (): bool => $this->composePassthru($composeFile, 'up -d --remove-orphans'),
        ];

        $plan[] = [
            'label' => 'Wait for healthy services',
            'detail' => "up to {$timeout}s",
            'run' => fn (): bool => $this->awaitHealthy($composeFile, $timeout),
        ];

        if (! $this->option('no-composer')) {
            $plan[] = [
                'label' => 'Install PHP dependencies',
                'detail' => 'composer install'.($env === 'production' ? ' --no-dev' : ''),
                'run' => fn (): bool => $this->installDependencies($composeFile, $service, $env),
            ];
        }

        if (! $this->option('no-assets')) {
            $plan[] = [
                'label' => 'Build front-end assets',
                'detail' => "npm ci && npm run build (in {$nodeService})",
                'run' => fn (): bool => $this->buildAssets($composeFile, $nodeService),
            ];
        }

        if (! $this->option('no-migrate')) {
            $plan[] = [
                'label' => 'Run database migrations',
                'detail' => 'artisan migrate --force',
                'run' => fn (): bool => $this->runMigrations($composeFile, $service),
            ];
        }

        if (! $this->option('no-optimize')) {
            $plan[] = [
                'label' => 'Warm up caches',
                'detail' => 'laradox:optimize',
                'run' => fn (): bool => $this->optimize($composeFile, $env, $service),
            ];
        }

        if ($this->option('maintenance')) {
            $plan[] = [
                'label' => 'Disable maintenance mode',
                'detail' => "artisan up (in {$service})",
                'run' => fn (): bool => $this->disableMaintenanceMode($composeFile, $service),
            ];
        }

        return $plan;
    }

    /**
     * Show the steps that make up this deployment.
     *
     * @param  array<int, array{label: string, detail: string, run: Closure(): bool}>  $plan
     */
    protected function printPlan(array $plan, string $composeFile, string $env): void
    {
        $this->newLine();
        $this->info('Deployment plan — '.basename($composeFile)." ({$env})");
        $this->newLine();

        foreach ($plan as $index => $step) {
            $this->line(sprintf('  %d. %s <comment>(%s)</comment>', $index + 1, $step['label'], $step['detail']));
        }

        $this->newLine();
    }

    /**
     * Ask the operator to confirm, unless confirmation was waived.
     *
     * @return int|null An exit code when the deployment must not run, null to proceed
     */
    protected function confirmDeployment(string $env, string $composeFile): ?int
    {
        if ($this->option('force')) {
            return null;
        }

        if (! $this->input->isInteractive()) {
            $this->error('✗ Deployment requires confirmation.');
            $this->line('Re-run with --force to deploy non-interactively.');
            $this->newLine();

            return self::FAILURE;
        }

        if (! $this->confirm("Deploy to {$env} using ".basename($composeFile).'?', false)) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        return null;
    }

    /**
     * Run the plan, reporting each step and restoring the app if one fails.
     *
     * @param  array<int, array{label: string, detail: string, run: Closure(): bool}>  $plan
     */
    protected function executePlan(array $plan, string $composeFile): int
    {
        $results = [];
        $failedAt = null;
        $startedAt = microtime(true);

        foreach ($plan as $index => $step) {
            $this->newLine();
            $this->info(sprintf('[%d/%d] %s', $index + 1, count($plan), $step['label']));

            $stepStartedAt = microtime(true);
            $succeeded = ($step['run'])();
            $duration = round(microtime(true) - $stepStartedAt, 2);

            $results[] = [
                $step['label'],
                $succeeded ? '<info>done</info>' : '<error>failed</error>',
                "{$duration}s",
            ];

            if (! $succeeded) {
                $failedAt = $step['label'];

                break;
            }
        }

        $total = round(microtime(true) - $startedAt, 2);

        $this->newLine();
        $this->table(['Step', 'Result', 'Duration'], $results);

        if ($failedAt !== null) {
            return $this->reportFailure($failedAt, $composeFile);
        }

        $this->info("✓ Deployment completed in {$total}s.");
        $this->newLine();
        $this->comment('Check the running services with: php artisan laradox:status --stats');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Report a failed deployment and leave the application reachable.
     */
    protected function reportFailure(string $failedStep, string $composeFile): int
    {
        $this->error("✗ Deployment failed at: {$failedStep}");
        $this->newLine();

        // Never leave the site behind a 503 because a later step broke.
        if ($this->maintenanceEngaged) {
            $this->warn('Bringing the application out of maintenance mode...');
            $this->disableMaintenanceMode($composeFile, $this->option('service'));
        }

        $this->line('Next steps:');
        $this->line('  1. Inspect the services:  php artisan laradox:status');
        $this->line('  2. Read the logs:         php artisan laradox:logs '.$this->option('service').' --tail=100');
        $this->line('  3. Roll back by checking out the previous revision and re-running this command.');
        $this->newLine();

        return self::FAILURE;
    }

    /**
     * Fast-forward the working tree to the latest revision.
     */
    protected function pullCode(): bool
    {
        if (! is_dir(base_path('.git'))) {
            $this->warn('⚠ Not a git repository — skipping the pull step.');

            return true;
        }

        $command = sprintf('git -C %s pull --ff-only', escapeshellarg(base_path()));

        $this->line("Executing: {$command}");
        passthru($command, $returnCode);

        return $returnCode === 0;
    }

    /**
     * Run a docker compose subcommand with live output.
     */
    protected function composePassthru(string $composeFile, string $arguments): bool
    {
        $command = sprintf(
            '%s -f %s %s',
            $this->getDockerComposeCommand(),
            escapeshellarg($composeFile),
            $arguments
        );

        $this->line("Executing: {$command}");
        passthru($command, $returnCode);

        return $returnCode === 0;
    }

    /**
     * Poll the services until they report healthy.
     */
    protected function awaitHealthy(string $composeFile, int $timeout): bool
    {
        $this->line('Waiting for the services to report healthy...');

        [$healthy, $statuses] = $this->waitForHealthyServices($composeFile, $timeout);

        foreach ($statuses as $status) {
            $marker = $this->serviceIsReady($status) ? '✓' : '✗';
            $health = $status['health'] === 'n/a' ? $status['state'] : $status['health'];

            $this->line("  {$marker} {$status['service']}: {$health}");
        }

        if (! $healthy) {
            $this->error("Services did not become healthy within {$timeout}s.");
        }

        return $healthy;
    }

    /**
     * Install the Composer dependencies inside the application container.
     */
    protected function installDependencies(string $composeFile, string $service, string $env): bool
    {
        $arguments = ['composer', 'install', '--no-interaction', '--prefer-dist', '--optimize-autoloader'];

        if ($env === 'production') {
            $arguments[] = '--no-dev';
        }

        return $this->runStreamed($composeFile, $service, $arguments);
    }

    /**
     * Build the front-end assets inside the node container.
     */
    protected function buildAssets(string $composeFile, string $nodeService): bool
    {
        if (! file_exists(base_path('package.json'))) {
            $this->warn('⚠ No package.json found — skipping the asset build.');

            return true;
        }

        if (! in_array($nodeService, $this->getRunningServices($composeFile), true)) {
            $this->warn("⚠ Service '{$nodeService}' is not running — skipping the asset build.");

            return true;
        }

        // `npm ci` needs a lockfile; fall back to install when there is none.
        $install = file_exists(base_path('package-lock.json')) ? 'ci' : 'install';

        if (! $this->runStreamed($composeFile, $nodeService, ['npm', $install])) {
            return false;
        }

        return $this->runStreamed($composeFile, $nodeService, ['npm', 'run', 'build']);
    }

    /**
     * Apply outstanding database migrations.
     */
    protected function runMigrations(string $composeFile, string $service): bool
    {
        return $this->runStreamed($composeFile, $service, ['php', 'artisan', 'migrate', '--force']);
    }

    /**
     * Warm the framework caches through the optimize command.
     */
    protected function optimize(string $composeFile, string $env, string $service): bool
    {
        return $this->call('laradox:optimize', [
            '--file' => $composeFile,
            '--environment' => $env,
            '--service' => $service,
            '--force' => true,
        ]) === self::SUCCESS;
    }

    /**
     * Put the application behind the maintenance page.
     */
    protected function enableMaintenanceMode(string $composeFile, string $service): bool
    {
        if (! in_array($service, $this->getRunningServices($composeFile), true)) {
            $this->warn("⚠ Service '{$service}' is not running — skipping maintenance mode.");

            return true;
        }

        if (! $this->runStreamed($composeFile, $service, ['php', 'artisan', 'down', '--retry=15'])) {
            return false;
        }

        $this->maintenanceEngaged = true;

        return true;
    }

    /**
     * Bring the application back online.
     */
    protected function disableMaintenanceMode(string $composeFile, string $service): bool
    {
        if (! in_array($service, $this->getRunningServices($composeFile), true)) {
            $this->warn("⚠ Service '{$service}' is not running — cannot disable maintenance mode.");
            $this->comment('Remove storage/framework/down manually once the containers are back.');

            return false;
        }

        $succeeded = $this->runStreamed($composeFile, $service, ['php', 'artisan', 'up']);

        if ($succeeded) {
            $this->maintenanceEngaged = false;
        }

        return $succeeded;
    }

    /**
     * Run a command in a container and echo its output as it is collected.
     *
     * @param  array<int, string>  $arguments
     */
    protected function runStreamed(string $composeFile, string $service, array $arguments): bool
    {
        [$exitCode, $output] = $this->runInContainer($composeFile, $service, $arguments);

        foreach ($output as $line) {
            $this->line("  {$line}");
        }

        return $exitCode === 0;
    }
}
