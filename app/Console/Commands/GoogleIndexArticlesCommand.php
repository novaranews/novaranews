<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\GoogleIndexingApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GoogleIndexArticlesCommand extends Command
{
    protected $signature = 'google:index-articles
                            {--type=* : İçerik türleri (varsayılan: guide, analysis, review). Tümü için: --type=all}
                            {--locale= : Sadece bu dil (örn. tr, en)}
                            {--limit= : Bu çalıştırmada gönderilecek azami URL (varsayılan: kalan günlük kota)}
                            {--min-body= : Asgari düz metin gövde uzunluğu (karakter); "uzun" içerikleri süzmek için}
                            {--force : Daha önce bildirilmiş / güncel olan içerikleri de yeniden gönder}
                            {--sleep=1 : API çağrıları arasında bekleme (saniye)}
                            {--dry-run : Hiçbir şey göndermeden gönderilecek URL listesini göster}';

    protected $description = 'Yayınlanmış makale URL\'lerini toplu olarak Google Indexing API\'ye gönderir (varsayılan: uzun içerik türleri).';

    /** @var string[] */
    private const LONGFORM_TYPES = ['guide', 'analysis', 'review'];

    public function handle(GoogleIndexingApiService $indexing): int
    {
        if (! $indexing->isConfigured()) {
            $this->error('Google Indexing yapılandırılmamış: GOOGLE_INDEXING_CREDENTIALS_PATH ile servis hesabı JSON dosyasını ayarlayın.');

            return self::FAILURE;
        }

        if (! $indexing->isFeatureEnabled()) {
            $this->warn('GOOGLE_INDEXING_ENABLED=false — CLI ile yine de gönderiliyor (admin butonu kapalı).');
        }

        $types = $this->resolveTypes();
        $locale = $this->option('locale') ? trim((string) $this->option('locale')) : null;
        $minBody = $this->option('min-body') !== null ? max(0, (int) $this->option('min-body')) : 0;
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');
        $sleep = max(0, (int) $this->option('sleep'));

        $remaining = $indexing->dailyRemaining();
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : $remaining;
        $budget = $dryRun ? PHP_INT_MAX : min($limit, $remaining);

        $this->line('Türler: <info>'.implode(', ', $types).'</info>'
            .($locale ? "  Dil: <info>{$locale}</info>" : '')
            .($minBody > 0 ? "  Min gövde: <info>{$minBody}</info>" : ''));
        $this->line("Günlük limit: {$indexing->dailyLimit()}  Kullanılan: {$indexing->dailyUsed()}  Kalan: {$remaining}");

        if (! $dryRun && $budget <= 0) {
            $this->warn('Bu çalıştırma için kota kalmadı (günlük limit dolu veya --limit=0).');

            return self::SUCCESS;
        }

        $query = Article::query()
            ->with('translations')
            ->published()
            ->whereIn('content_type', $types);

        if ($locale !== null && $locale !== '') {
            $query->where('locale', $locale);
        }

        $query->orderByDesc('is_editors_pick')
            ->orderByDesc('is_breaking')
            ->orderByDesc('published_at');

        $submitted = 0;
        $skipped = 0;
        $failed = 0;
        $rows = [];

        foreach ($query->cursor() as $article) {
            if (! $dryRun && $submitted >= $budget) {
                break;
            }

            $url = $this->eligibleUrl($article, $minBody);
            if ($url === null) {
                $skipped++;

                continue;
            }

            if (! $force && ! $article->googleIndexingNeedsNotify()) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $rows[] = [$article->id, $article->content_type, $article->locale, $url];
                $submitted++;

                continue;
            }

            $result = $indexing->publishUrlUpdated($url);

            if ($result['ok']) {
                DB::table('articles')->where('id', $article->id)->update([
                    'google_indexing_notified_at' => now(),
                ]);
                $submitted++;
                $this->line("  <info>OK</info>   #{$article->id}  {$url}");
            } elseif ($result['message'] === 'daily_limit') {
                $this->warn('Günlük kotaya ulaşıldı, durduruluyor.');
                break;
            } else {
                $failed++;
                $this->line("  <error>FAIL</error> #{$article->id}  {$url}  — ".$result['message']);
            }

            if ($sleep > 0) {
                sleep($sleep);
            }
        }

        if ($dryRun) {
            if ($rows === []) {
                $this->warn('Gönderilecek uygun içerik bulunamadı.');
            } else {
                $this->table(['ID', 'Tür', 'Dil', 'URL'], $rows);
                $this->info(count($rows).' URL gönderilebilir (dry-run).');
            }

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info("Bitti. Gönderilen: {$submitted}  Atlanan: {$skipped}  Hatalı: {$failed}");

        return $failed > 0 && $submitted === 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function resolveTypes(): array
    {
        $opt = (array) $this->option('type');
        $opt = array_values(array_filter(array_map('trim', $opt)));

        if ($opt === []) {
            return self::LONGFORM_TYPES;
        }

        if (in_array('all', $opt, true)) {
            return Article::CONTENT_TYPES;
        }

        $valid = array_values(array_intersect($opt, Article::CONTENT_TYPES));

        return $valid !== [] ? $valid : self::LONGFORM_TYPES;
    }

    private function eligibleUrl(Article $article, int $minBody): ?string
    {
        if (! $article->isPublishedLive()) {
            return null;
        }

        $tr = $article->translations->firstWhere('locale', $article->locale);
        if (! $tr || ! filled($tr->slug) || ($tr->robots_noindex ?? false)) {
            return null;
        }

        if ($minBody > 0) {
            $plain = trim(html_entity_decode(strip_tags((string) $tr->getRawOriginal('body'))));
            if (mb_strlen($plain) < $minBody) {
                return null;
            }
        }

        $url = $article->publicUrl($article->locale);
        if ($url === null || $url === '' || $url === '#' || ! str_starts_with($url, 'https://')) {
            return null;
        }

        return $url;
    }
}
