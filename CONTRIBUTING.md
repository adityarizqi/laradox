# Contributing to Laradox

Thank you for considering contributing to Laradox! We welcome contributions from the community.

## How to Contribute

### Reporting Bugs

If you find a bug, please open an issue on GitHub with:
- A clear description of the issue
- Steps to reproduce the problem
- Expected vs actual behavior
- Your environment details (OS, Docker version, PHP version)

### Suggesting Enhancements

Feature requests are welcome! Please open an issue describing:
- The feature you'd like to see
- Why it would be useful
- How it might work

### Pull Requests

1. Fork the repository
2. Create a new branch for your feature (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Commit your changes (`git commit -m 'Add some amazing feature'`)
5. Push to the branch (`git push origin feature/amazing-feature`)
6. Open a Pull Request

#### PR Guidelines

- Follow PSR-12 coding standards
- Add tests for new features (both feature and unit tests when applicable)
- Ensure all tests pass: `composer test`
- Update documentation as needed (README.md, QUICKSTART.md, tests/README.md)
- Keep commits focused and atomic
- Write clear commit messages
- Mock external dependencies (Docker, mkcert) in tests

### Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Run tests: `composer test`
4. Check test output: `vendor/bin/phpunit --testdox`
5. Generate coverage report: `vendor/bin/phpunit --coverage-html build/coverage`

### Test Requirements

When adding new features:
- Add feature tests in `tests/Feature/` for end-to-end functionality
- Add unit tests in `tests/Unit/` for individual components
- Mock external commands (exec, passthru) to avoid requiring Docker/mkcert in tests
- **All tests must pass before submitting PR** - no exceptions
- Aim for high code coverage on new code
- Test across multiple scenarios and edge cases

## Code of Conduct

Be respectful and inclusive. We want this to be a welcoming community for everyone.

## Questions?

Feel free to open an issue for any questions about contributing.
