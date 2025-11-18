# Laradox Tests

This directory contains the test suite for the Laradox package.

## Test Structure

Laradox includes a comprehensive test suite organized into two main categories:

### Feature Tests
End-to-end tests for complete command functionality:
- `ConfigurationTest.php` - Configuration loading and validation
- `InstallCommandTest.php` - Package installation workflow
- `SetupSSLCommandTest.php` - SSL certificate generation
- `UpCommandTest.php` - Container startup with Docker/SSL detection
- `DownCommandTest.php` - Container shutdown

### Unit Tests
Focused tests for individual command components:
- `InstallCommandTest.php` - Command signature, options, and file operations
- `UpCommandTest.php` - Command construction, SSL handling, and protocol detection
- `DownCommandTest.php` - Command validation and environment handling
- `LaradoxServiceProviderTest.php` - Service provider registration
- `DockerCheckTest.php` - Docker detection, OS detection, and installation prompts
- `SetupSSLCommandTest.php` - mkcert detection and SSL generation

## Running Tests

Run all tests:
```bash
composer test
```

Or using PHPUnit directly:
```bash
vendor/bin/phpunit
```

Run specific test directories:
```bash
# Run all feature tests
vendor/bin/phpunit tests/Feature/

# Run all unit tests
vendor/bin/phpunit tests/Unit/
```

Run specific test files:
```bash
# Feature tests
vendor/bin/phpunit tests/Feature/InstallCommandTest.php
vendor/bin/phpunit tests/Feature/UpCommandTest.php

# Unit tests
vendor/bin/phpunit tests/Unit/InstallCommandTest.php
vendor/bin/phpunit tests/Unit/DockerCheckTest.php
```

Run with test output details:
```bash
vendor/bin/phpunit --testdox
```

Run with coverage:
```bash
vendor/bin/phpunit --coverage-html build/coverage
```

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

All test combinations are tested in GitHub Actions CI/CD pipeline.

## Test Requirements

**All tests must pass before merging any changes.** The test suite ensures:
- Command functionality works correctly
- OS detection and installation prompts work across platforms
- SSL certificate generation behaves as expected
- Docker integration functions properly
- All edge cases are handled gracefully

## CI/CD

Tests are designed to run in CI environments where Docker and mkcert may not be available. External command executions are mocked or tested for their invocation rather than actual execution.
