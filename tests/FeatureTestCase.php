<?php

namespace Laradox\Tests;

use Illuminate\Support\Facades\File;

abstract class FeatureTestCase extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Setup the test environment.
     */
    protected function tearDown(): void
    {
        // Clean up test artifacts after each test
        $this->cleanupTestFiles();
        
        parent::tearDown();
    }

    /**
     * Clean up test files.
     */
    protected function cleanupTestFiles(): void
    {
        try {
            if (!function_exists('base_path')) {
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
        } catch (\Throwable $e) {
            // Silently catch any errors during cleanup
        }
    }
}
