# NovaNews

[![CI](https://github.com/novaranews/novaranews/actions/workflows/ci.yml/badge.svg)](https://github.com/novaranews/novaranews/actions/workflows/ci.yml)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](LICENSE)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4.svg)](https://www.php.net/)

![NovaNews](public/og-default.jpg)

NovaNews is an open-source, multilingual news publishing platform built with Laravel. It combines a newsroom-oriented CMS with RSS ingestion, optional AI-assisted article generation, editorial controls, Google News SEO, and a responsive public site.

> **Early open-source release:** NovaNews is production-derived software that is being generalized for community use. Review the security and deployment notes before exposing a new installation to the internet.

## Live demo

Explore the public demo at [novaranews.com](https://novaranews.com). The site is maintained as a demonstration of the open-source project; editorial updates are not guaranteed.

## Highlights

- Multilingual publishing with localized routes, categories, articles, authors, and static pages
- Article, category, media, comment, redirect, and settings administration
- RSS source ingestion and queue-based processing
- Optional Anthropic-powered article generation and Unsplash image lookup
- Editorial guardrails, revisions, audit logs, previews, and content readiness checks
- Google News sitemaps, locale sitemaps, RSS feeds, structured data, canonical URLs, and hreflang
- Optional Google Indexing API and reCAPTCHA integrations
- Two-factor authentication for editorial accounts
- Cache, queue, Redis, and scheduled-task support
- Built-in security audit command

## Requirements

- PHP 8.2 or newer
- Composer 2
- Node.js 20 or newer and npm
- SQLite, MySQL 8+, or PostgreSQL
- PHP extensions normally required by Laravel, including ctype, curl, dom, fileinfo, filter, hash, mbstring, openssl, pcre, PDO, session, tokenizer, and XML

## Quick start

```bash
git clone https://github.com/novaranews/novaranews.git
cd novaranews
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
npm install
npm run build
php artisan novara:create-admin
php artisan storage:link
php artisan serve
```

Open `http://localhost:8000/admin` and sign in with the administrator account created by the command.

New installations intentionally contain no default administrator, password, or demo articles. Create the first administrator with `novara:create-admin`, then add or import content from the administration area.

On Windows PowerShell, replace `cp .env.example .env` with:

```powershell
Copy-Item .env.example .env
```

## Background jobs

NovaNews uses Laravel queues for RSS and AI generation. During development:

```bash
php artisan queue:work
```

For production, run a supervised queue worker and configure Laravel's scheduler:

```cron
* * * * * cd /path/to/novaranews && php artisan schedule:run >> /dev/null 2>&1
```

The example news-fetch and generation schedules in `routes/console.php` are disabled by default. Review their frequency and API costs before enabling them.

## Deployment

For production web-root, permissions, queues, scheduler, proxy, TLS, backup, and update guidance, read [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md). Redis-specific guidance is available in [docs/REDIS_CACHE_QUEUE.md](docs/REDIS_CACHE_QUEUE.md).

## Optional integrations

The application runs without external AI, image, indexing, analytics, or CAPTCHA services. Configure only the integrations you need in `.env` and the administration settings.

- `ANTHROPIC_API_KEY`: AI-assisted article generation
- `UNSPLASH_ACCESS_KEY`: image search
- `GOOGLE_INDEXING_*`: Google Indexing API
- `RECAPTCHA_*`: public form protection
- `NOVARANEWS_SOURCE_CODE_URL`: public repository URL displayed in the footer for AGPL source access

Never commit API keys, service-account JSON files, database exports, logs, or uploaded media. The provided `.gitignore` excludes these paths.

## Quality checks

```bash
composer test
npm run build
php artisan security:audit
```

## Responsible publishing

Operators are responsible for copyright, attribution, fact-checking, corrections, privacy, API usage, and compliance with local law. AI-generated material should be reviewed by a human editor before publication.

## Contributing

Bug reports and pull requests are welcome. Read [CONTRIBUTING.md](CONTRIBUTING.md) before contributing. Please report security vulnerabilities privately as described in [SECURITY.md](SECURITY.md).

Release history is maintained in [CHANGELOG.md](CHANGELOG.md).

## Support the project

If NovaNews is useful to you, you can support its continued maintenance with a [cryptocurrency donation](https://plisio.net/donate/jYjnfaHi). Contributions are optional and do not affect access to the project or its AGPL license.

## License

NovaNews is licensed under the GNU Affero General Public License, version 3 or later (`AGPL-3.0-or-later`). If you modify NovaNews and make it available to users over a network, the license requires you to offer those users the corresponding source code of your modified version. See [LICENSE](LICENSE).
