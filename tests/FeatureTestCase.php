<?php

namespace Laradox\Tests;

use Illuminate\Support\Facades\File;
use Throwable;

abstract class FeatureTestCase extends TestCase
{
    /**
     * Compose project name used for the duration of a single test.
     */
    protected string $composeProject = '';

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // The up/down commands shell out to a real Docker daemon, and Compose
        // derives its project name from the working directory - which is the same
        // testbench skeleton for every test. A container left behind by one test
        // was therefore visible to the next one, which then reported "containers
        // are already running" and took the interactive restart branch it had no
        // expectation for. Giving each test its own project keeps that isolated.
        $this->composeProject = 'laradox-test-'.bin2hex(random_bytes(6));
        putenv("COMPOSE_PROJECT_NAME={$this->composeProject}");
    }

    /**
     * Setup the test environment.
     */
    protected function tearDown(): void
    {
        // Clean up test artifacts after each test
        $this->stopTestContainers();
        $this->cleanupTestFiles();

        putenv('COMPOSE_PROJECT_NAME');

        parent::tearDown();
    }

    /**
     * Remove containers a test started, so they neither leak into the next test
     * nor outlive the suite on the host.
     */
    protected function stopTestContainers(): void
    {
        try {
            if (! function_exists('base_path') || ! $this->dockerComposeAvailable()) {
                return;
            }

            foreach (['development', 'production'] as $env) {
                $composeFile = base_path("docker-compose.{$env}.yml");

                if (! File::exists($composeFile)) {
                    continue;
                }

                exec(sprintf(
                    'docker compose -f %s down --remove-orphans --timeout 0 2>/dev/null',
                    escapeshellarg($composeFile)
                ));
            }
        } catch (Throwable $e) {
            // Cleanup is best effort; never fail a test because of it.
        }
    }

    /**
     * Determine whether Docker Compose can be reached, cached for the whole run.
     */
    protected function dockerComposeAvailable(): bool
    {
        static $available = null;

        if ($available === null) {
            exec('docker compose version 2>/dev/null', $output, $returnCode);
            $available = $returnCode === 0;
        }

        return $available;
    }

    /**
     * Clean up test files.
     */
    protected function cleanupTestFiles(): void
    {
        try {
            if (! function_exists('base_path')) {
                return;
            }

            $filesToClean = [
                base_path('docker'),
                base_path('composer'),
                base_path('npm'),
                base_path('php'),
                base_path('docker-compose.development.yml'),
                base_path('docker-compose.production.yml'),
                config_path('laradox.php'),
            ];

            foreach ($filesToClean as $file) {
                if (File::exists($file)) {
                    if (File::isDirectory($file)) {
                        File::deleteDirectory($file);
                    } else {
                        File::delete($file);
                    }
                }
            }
        } catch (Throwable $e) {
            // Silently catch any errors during cleanup
        }
    }
}
