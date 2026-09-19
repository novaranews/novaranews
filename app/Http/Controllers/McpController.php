<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class McpController extends Controller
{
    private const LOCALES           = ['en', 'tr', 'de', 'fr', 'es'];
    private const PROTOCOL_VERSION  = '2024-11-05';

    public function handle(Request $request): JsonResponse
    {
        $body = $request->json()->all();

        if (empty($body)) {
            return $this->parseError();
        }

        // Batch request
        if (array_is_list($body) && isset($body[0])) {
            $responses = array_values(array_filter(array_map(fn($r) => $this->dispatch($r), $body)));
            return response()->json($responses);
        }

        $result = $this->dispatch($body);

        return $result !== null
            ? response()->json($result)
            : response()->json(null, 204);
    }

    private function dispatch(array $req): ?array
    {
        $id             = $req['id'] ?? null;
        $method         = $req['method'] ?? '';
        $params         = $req['params'] ?? [];
        $isNotification = ! array_key_exists('id', $req);

        try {
            $result = match ($method) {
                'initialize'                => $this->initialize(),
                'ping'                      => (object) [],
                'notifications/initialized' => null,
                'tools/list'                => $this->toolsList(),
                'tools/call'                => $this->toolsCall($params),
                'resources/list'            => $this->resourcesList(),
                'resources/read'            => $this->resourcesRead($params),
                default                     => throw new \Exception("Method not found: {$method}", -32601),
            };

            if ($isNotification || $result === null) {
                return null;
            }

            return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];

        } catch (\Exception $e) {
            if ($isNotification) {
                return null;
            }

            return ['jsonrpc' => '2.0', 'id' => $id, 'error' => [
                'code'    => $e->getCode() ?: -32603,
                'message' => $e->getMessage(),
            ]];
        }
    }

    // ── MCP protocol handlers ─────────────────────────────────────────────────

    private function initialize(): array
    {
        return [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'serverInfo'      => [
                'name'    => 'Novara News MCP Server',
                'version' => '1.0.0',
            ],
            'capabilities' => [
                'tools'     => ['listChanged' => false],
                'resources' => ['listChanged' => false, 'subscribe' => false],
            ],
        ];
    }

    private function toolsList(): array
    {
        return [
            'tools' => [
                [
                    'name'        => 'get_articles',
                    'description' => 'Fetch recent published articles. Returns title, excerpt, URL, author, category, and publication date.',
                    'inputSchema' => [
                        'type'       => 'object',
                        'properties' => [
                            'locale'       => ['type' => 'string', 'enum' => self::LOCALES, 'default' => 'en', 'description' => 'Language code'],
                            'limit'        => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20, 'default' => 10],
                            'category_key' => ['type' => 'string', 'description' => 'Filter by category key (e.g. artificial-intelligence)'],
                            'content_type' => ['type' => 'string', 'enum' => Article::CONTENT_TYPES, 'description' => 'Filter by content type'],
                        ],
                        'required' => [],
                    ],
                ],
                [
                    'name'        => 'get_article',
                    'description' => 'Fetch a single article by locale and slug. Returns full HTML body, meta, and sources.',
                    'inputSchema' => [
                        'type'       => 'object',
                        'properties' => [
                            'locale' => ['type' => 'string', 'enum' => self::LOCALES],
                            'slug'   => ['type' => 'string', 'description' => 'Article slug from URL'],
                        ],
                        'required' => ['locale', 'slug'],
                    ],
                ],
                [
                    'name'        => 'get_categories',
                    'description' => 'List all article categories with names and slugs for the given locale.',
                    'inputSchema' => [
                        'type'       => 'object',
                        'properties' => [
                            'locale' => ['type' => 'string', 'enum' => self::LOCALES, 'default' => 'en'],
                        ],
                        'required' => [],
                    ],
                ],
                [
                    'name'        => 'get_authors',
                    'description' => 'List editorial authors with names, titles, and profile URLs.',
                    'inputSchema' => [
                        'type'       => 'object',
                        'properties' => [
                            'locale' => ['type' => 'string', 'enum' => self::LOCALES, 'default' => 'en', 'description' => 'Language code for title/bio'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
        ];
    }

    private function toolsCall(array $params): array
    {
        $name = $params['name'] ?? '';
        $args = $params['arguments'] ?? [];

        $data = match ($name) {
            'get_articles'   => $this->getArticles($args),
            'get_article'    => $this->getArticle($args),
            'get_categories' => $this->getCategories($args),
            'get_authors'    => $this->getAuthors($args),
            default          => throw new \Exception("Unknown tool: {$name}", -32601),
        };

        return [
            'content' => [
                ['type' => 'text', 'text' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)],
            ],
        ];
    }

    private function resourcesList(): array
    {
        $base = rtrim((string) config('app.url'), '/');
        $resources = [];

        foreach (config('novaranews.locales', ['en']) as $locale) {
            $resources[] = [
                'uri' => $base.'/'.$locale.'/feed.xml',
                'name' => 'RSS Feed ('.strtoupper($locale).')',
                'mimeType' => 'application/rss+xml',
            ];
        }

        $resources[] = [
            'uri' => $base.'/sitemap.xml',
            'name' => 'Sitemap Index',
            'mimeType' => 'application/xml',
        ];

        return ['resources' => $resources];
    }

    private function resourcesRead(array $params): array
    {
        $uri = $params['uri'] ?? '';

        $allowed = array_column($this->resourcesList()['resources'], 'uri');

        if (! in_array($uri, $allowed, true)) {
            throw new \Exception('Resource not found', -32602);
        }

        $response = Http::timeout(10)->get($uri);
        if (! $response->successful()) {
            throw new \Exception('Failed to read resource', -32603);
        }
        $content = $response->body();

        $mimeType = match (true) {
            str_ends_with($uri, '.xml') => 'application/xml',
            str_ends_with($uri, '.txt') => 'text/plain',
            default                     => 'application/octet-stream',
        };

        return [
            'contents' => [
                ['uri' => $uri, 'mimeType' => $mimeType, 'text' => $content],
            ],
        ];
    }

    // ── Tool implementations ───────────────────────────────────────────────────

    private function getArticles(array $args): array
    {
        $locale      = $this->validLocale($args['locale'] ?? 'en');
        $limit       = min((int) ($args['limit'] ?? 10), 20);
        $categoryKey = $args['category_key'] ?? null;
        $contentType = $args['content_type'] ?? null;

        $query = Article::with(['translations', 'category.translations', 'author'])
            ->published()
            ->forLocale($locale)
            ->latest('published_at');

        if ($categoryKey) {
            $query->whereHas('category', fn($q) => $q->where('key', $categoryKey));
        }

        if ($contentType && in_array($contentType, Article::CONTENT_TYPES, true)) {
            $query->where('content_type', $contentType);
        }

        return $query->limit($limit)->get()->map(fn(Article $a) => [
            'title'        => $a->translate($locale)?->title,
            'excerpt'      => $a->translate($locale)?->excerpt,
            'url'          => $a->publicUrl($locale),
            'published_at' => $a->published_at?->toIso8601String(),
            'content_type' => $a->content_type,
            'category'     => $a->category?->translate($locale)?->name,
            'author'       => $a->author?->name,
        ])->values()->all();
    }

    private function getArticle(array $args): array
    {
        $locale = $this->validLocale($args['locale'] ?? 'en');
        $slug   = trim($args['slug'] ?? '');

        if ($slug === '') {
            throw new \Exception('slug is required', -32602);
        }

        $tr = ArticleTranslation::with(['article.category.translations', 'article.author'])
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->first();

        if (! $tr || ! $tr->article->isPublishedLive()) {
            throw new \Exception('Article not found', -32602);
        }

        $a = $tr->article;

        return [
            'title'        => $tr->title,
            'excerpt'      => $tr->excerpt,
            'body'         => $tr->body,
            'url'          => $a->publicUrl($locale),
            'published_at' => $a->published_at?->toIso8601String(),
            'content_type' => $a->content_type,
            'category'     => $a->category?->translate($locale)?->name,
            'author'       => $a->author?->name,
            'sources'      => $tr->sources ?? [],
            'meta'         => [
                'title'       => $tr->meta_title,
                'description' => $tr->meta_description,
            ],
        ];
    }

    private function getCategories(array $args): array
    {
        $locale = $this->validLocale($args['locale'] ?? 'en');

        return Category::with('translations')
            ->orderBy('sort_order')
            ->get()
            ->map(fn(Category $c) => [
                'key'  => $c->key,
                'name' => $c->translate($locale)?->name,
                'slug' => $c->translate($locale)?->slug,
                'url'  => ($slug = $c->translate($locale)?->slug)
                    ? route('category.show', ['locale' => $locale, 'categorySlug' => $slug])
                    : null,
            ])
            ->filter(fn($row) => $row['name'] !== null)
            ->values()
            ->all();
    }

    private function getAuthors(array $args): array
    {
        $locale = $this->validLocale($args['locale'] ?? 'en');

        return User::with('profileTranslations')
            ->whereHas('articles', fn($q) => $q->published())
            ->get()
            ->map(fn(User $u) => [
                'name'        => $u->name,
                'title'       => $u->profileTitleForLocale($locale),
                'profile_url' => $u->profileUrl($locale),
                'avatar_url'  => $u->avatar_url,
                'twitter'     => $u->twitterHandle(),
            ])
            ->values()
            ->all();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validLocale(string $locale): string
    {
        return in_array($locale, self::LOCALES, true) ? $locale : 'en';
    }

    private function parseError(): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id'      => null,
            'error'   => ['code' => -32700, 'message' => 'Parse error'],
        ], 400);
    }
}
