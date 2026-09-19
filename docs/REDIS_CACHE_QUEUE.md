# Redis, önbellek ve kuyruk (üretim önerisi)

Bu proje varsayılan olarak **dosya / veritabanı** sürücüleriyle çalışır. Trafik veya çoklu worker ihtiyacında **Redis** kullanmak yaygın bir adımdır.

## 1. Sunucu

- Redis servisi kurulu ve dinliyor olmalı (ör. `127.0.0.1:6379`).
- PHP tarafında genelde **phpredis** eklentisi veya Composer ile **`predis/predis`** kullanılır (Laravel dokümantasyonuna göre tercih).

## 2. `.env` örnekleri

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

Deploy sonrası:

```bash
php artisan config:cache
```

## 3. Kuyruk worker

Cron ile `schedule:run` kullanıyorsanız, kuyruk işleri için ayrıca süreç gerekir:

```bash
php artisan queue:work redis --sleep=3 --tries=3
```

(systemd, Supervisor veya benzeri ile sürekli çalıştırın.)

## 4. Ne zaman gerekir?

- Oturumların birden fazla uygulama örneği arasında paylaşılması.
- Rate limit / cache için kalıcı ve hızlı depolama.
- Uzun süren işler (e-posta, RSS, AI taslakları) için **queue**.

İçerik az ve tek sunucuysa, başlangıçta **dosya cache + `database` session** da yeterli olabilir; Redis “büyüme” adımıdır.

## 5. Deploy ve site önbelleği

`docs/deploy.sh` içinde `optimize:clear` sonrası seeder bittikten sonra **`php artisan cache:clear`** çalıştırılır. Bu, Redis veya `file` sürücüsündeki uygulama önbelleğini (ör. `site.home.data.{locale}`, `site.nav.categories`) temizler; yeni içerik ve kategori/statik senkron sonrası ana sayfanın eski veriyi göstermesini azaltır.

Makale kaydı/silindiğinde `ArticleObserver` da aynı anahtarları temizler.
