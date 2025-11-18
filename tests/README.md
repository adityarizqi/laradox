# Laradox Tests

This directory contains the test suite for the Laradox package.

## Running Tests

Run all tests:
```bash
composer test
```

Or using PHPUnit directly:
```bash
vendor/bin/phpunit
```

Run specific test suites:
```bash
vendor/bin/phpunit --testsuite="Laradox Test Suite"
```

Run with coverage:
```bash
vendor/bin/phpunit --coverage-html build/coverage
```

## Test Structure

### Unit Tests (`tests/Unit/`)
- `LaradoxServiceProviderTest.php` - Tests for the service provider registration and configuration

### Feature Tests (`tests/Feature/`)
- `InstallCommandTest.php` - Tests for the `laradox:install` command
- `UpCommandTest.php` - Tests for the `laradox:up` command
- `DownCommandTest.php` - Tests for the `laradox:down` command
- `SetupSSLCommandTest.php` - Tests for the `laradox:setup-ssl` command
- `ConfigurationTest.php` - Tests for configuration values and environment overrides

## Test Coverage

The test suite covers:
- ✅ Service provider registration and bootstrapping
- ✅ Command registration and availability
- ✅ Configuration merging and publishing
- ✅ File and directory creation
- ✅ Script permissions and execution
- ✅ Docker Compose file handling
- ✅ SSL certificate setup
- ✅ Environment variable overrides
- ✅ Error handling and validation

## Writing New Tests

1. Extend the `Laradox\Tests\TestCase` class
2. Use the `#[Test]` annotation or prefix methods with `test_`
3. Clean up test artifacts in tearDown (handled automatically by base TestCase)
4. Mock external dependencies (Docker, mkcert) when needed

Example:
```php
<?php

namespace Laradox\Tests\Feature;

use Laradox\Tests\TestCase;

class MyNewTest extends TestCase
{
    #[Test]
    public function it_does_something(): void
    {
        // Your test code here
        $this->assertTrue(true);
    }
}
```

## Dependencies

- PHPUnit ^10.0|^11.0
- Orchestra Testbench:
  - ^8.0 for Laravel 10.x
  - ^9.0 for Laravel 11.x
  - ^10.0 for Laravel 12.x

## Test Matrix

The test suite is tested against multiple PHP and Laravel versions in CI:

| Laravel Version | PHP Versions | Testbench Version |
|----------------|--------------|-------------------|
| 10.x | 8.2, 8.3 | ^8.0 |
| 11.x | 8.2, 8.3, 8.4 | ^9.0 |
| 12.x | 8.2, 8.3, 8.4 | ^10.0 |

**Total:** 8 test combinations running in GitHub Actions

## CI/CD

Tests are designed to run in CI environments where Docker and mkcert may not be available. External command executions are mocked or tested for their invocation rather than actual execution.
