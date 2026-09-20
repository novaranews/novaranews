# Redis, cache, and queues in production

NovaNews uses file and database drivers by default. Redis is a common next step when traffic grows, multiple application instances need shared state, or background workers need higher throughput.

## 1. Server requirements

- A Redis service must be installed and listening, commonly on `127.0.0.1:6379`.
- PHP normally connects through the **phpredis** extension or the Composer package **`predis/predis`**. Choose the client supported by your deployment environment.

## 2. Example `.env` configuration

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

Refresh the cached configuration after deployment:

```bash
php artisan config:cache
```

## 3. Queue worker

Laravel's scheduler does not process queued jobs. Run a separate queue worker when queues are enabled:

```bash
php artisan queue:work redis --sleep=3 --tries=3
```

Keep the worker running under systemd, Supervisor, or a comparable process manager. On shared hosting that prohibits persistent workers, use a short-lived `queue:work --stop-when-empty` command from an allowed cron interval instead.

## 4. When Redis helps

- Sharing sessions across multiple application instances.
- Providing fast, persistent storage for rate limits and application caches.
- Processing long-running work such as email, RSS ingestion, and AI drafts.

For a small single-server installation, file cache and database-backed sessions and queues are a reasonable starting point. Redis is an optional scaling step.

## 5. Deployment and site cache

Clear the application cache after synchronizing seeded content so home-page, navigation, category, and static-page caches cannot retain stale values:

```bash
php artisan cache:clear
```

`ArticleObserver` also clears relevant cache keys when an article is saved or deleted.
