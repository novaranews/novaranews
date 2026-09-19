# Contributing to NovaNews

Thanks for helping improve NovaNews.

## Before opening a pull request

1. Open an issue for substantial behavioral or architectural changes.
2. Keep pull requests focused on one problem.
3. Add or update tests for behavioral changes.
4. Run `composer test` and `npm run build`.
5. Do not include credentials, database exports, production content, logs, or uploaded media.

## Development setup

Follow the quick-start guide in `README.md`. Use SQLite for the simplest local and test setup. External API integrations should be disabled or mocked in tests.

## Code style

- Follow the existing Laravel conventions and PSR-12 style.
- Run `vendor/bin/pint` before submitting PHP changes.
- Keep translations synchronized when adding user-facing strings.
- Treat public URLs and redirects as untrusted input.

By submitting a contribution, you agree that it may be distributed under the project's AGPL-3.0-or-later license.

