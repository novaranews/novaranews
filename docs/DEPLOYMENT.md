# Production deployment

This guide covers the deployment requirements shared by VPS, managed hosting, and cPanel-style environments. Provider interfaces differ, but the Laravel application lifecycle remains the same.

## Requirements

- PHP 8.2 or newer with the extensions listed in the root README
- Composer 2
- MySQL 8+, PostgreSQL, or SQLite
- A web server whose document root can point to NovaNews's `public` directory
- Node.js 20+ on the build machine, unless prebuilt `public/build` assets are uploaded
- HTTPS for every production hostname

## Directory layout and web root

Upload or clone the complete repository outside the public web root whenever the hosting layout allows it. Configure the domain document root as:

```text
/path/to/novaranews/public
```

Never expose `.env`, `vendor`, `storage`, database exports, service-account JSON files, or deployment backups through the web server.

On hosting where the application lives at `/home/USER/novaranews.com`, the domain document root should be `/home/USER/novaranews.com/public`.

## Environment configuration

Create `.env` from `.env.example`, generate a unique key, and set production-safe values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
SESSION_SECURE_COOKIE=true
```

Configure the database, mail transport, and only the optional integrations you intend to use. Never reuse credentials from another installation.

## Initial deployment

Run these commands from the project root:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate
php artisan migrate --force
npm ci
npm run build
php artisan storage:link
php artisan novara:create-admin
php artisan optimize
```

If Node.js is unavailable on the server, run `npm ci && npm run build` on a trusted build machine and upload the generated `public/build` directory with the release.

The web-server user must be able to write to `storage` and `bootstrap/cache`. Start with the least permissive ownership and mode supported by the host; do not make the entire application world-writable.

## Queues and scheduler

RSS ingestion and AI generation use Laravel queues. On a VPS, keep a queue worker under systemd or Supervisor:

```bash
php artisan queue:work --sleep=3 --tries=3
```

Laravel's scheduler requires one cron entry:

```cron
* * * * * cd /path/to/novaranews && php artisan schedule:run >> /dev/null 2>&1
```

The example news schedules are disabled in `routes/console.php`. Enable them only after reviewing API cost, copyright, and editorial requirements.

Shared hosts may prohibit persistent workers or cron intervals shorter than five minutes. In that environment, use the provider's supported interval with a short-lived worker such as:

```bash
php artisan queue:work --stop-when-empty --max-time=240 --tries=3
```

Do not configure a queue worker when RSS and AI features are not used.

## Reverse proxies and TLS

Terminate TLS with a certificate valid for every public hostname. When Cloudflare or another reverse proxy is used, validate the origin certificate and use strict origin verification. Configure trusted proxies narrowly when the hosting network provides stable proxy ranges; review the permissive `TRUSTED_PROXIES=*` example before production use.

Verify at least these endpoints after deployment:

```text
/
/up
/en
/robots.txt
/sitemap.xml
/.well-known/api-catalog
```

## Updating an installation

Back up the database and uploaded media before every update, then run:

```bash
php artisan down
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan up
```

Use an atomic release strategy instead of maintenance mode when uninterrupted deployment is required.

## Backups and monitoring

Back up the database, `.env`, and `storage/app/public` to a separate system. Test restoration regularly. Monitor Laravel logs, failed queue jobs, disk usage, certificate expiry, HTTP 5xx rates, and hosting resource limits.

Run the built-in content and redirect audit after imports or migrations:

```bash
php artisan security:audit
```
