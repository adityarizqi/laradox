<?php

namespace Laradox\Tests;

use Illuminate\Support\Facades\File;

abstract class FeatureTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up test artifacts before each test
        $this->cleanupTestFiles();
    }

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
    }
}
