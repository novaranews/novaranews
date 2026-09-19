<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarkdownNegotiation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldConvert($request, $response)) {
            return $response;
        }

        $html = $response->getContent();
        $markdown = $this->toMarkdown($html);

        $response->setContent($markdown);
        $response->headers->set('Content-Type', 'text/markdown; charset=UTF-8');
        $response->headers->set('Vary', 'Accept');
        $tokenCount = preg_match_all('/\S+/u', $markdown, $m);
        $response->headers->set('X-Markdown-Tokens', (string) ($tokenCount ?: 0));

        return $response;
    }

    private function shouldConvert(Request $request, Response $response): bool
    {
        if (! str_contains($request->header('Accept', ''), 'text/markdown')) {
            return false;
        }

        if (! in_array($request->method(), ['GET'])) {
            return false;
        }

        if ($response->isRedirection()) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html') || $contentType === '';
    }

    private function toMarkdown(string $html): string
    {
        $converterClass = \League\HTMLToMarkdown\HtmlConverter::class;

        if (class_exists($converterClass)) {
            $converter = new $converterClass([
                'strip_tags'        => true,
                'suppress_errors'   => true,
                'hard_break'        => false,
                'remove_nodes'      => 'head header script style noscript nav footer aside form',
            ]);

            return trim($converter->convert($html));
        }

        return $this->fallbackMarkdown($html);
    }

    private function fallbackMarkdown(string $html): string
    {
        $html = preg_replace('#<(head|header|script|style|noscript|nav|footer|aside|form)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#<!--.*?-->#s', '', $html) ?? $html;
        $html = preg_replace('/<!doctype[^>]*>/i', '', $html) ?? $html;

        $html = preg_replace_callback('#<h([1-6])\b[^>]*>(.*?)</h\1>#is', function (array $matches): string {
            $level = (int) $matches[1];
            $text = $this->cleanMarkdownText($matches[2]);

            return $text !== '' ? "\n\n".str_repeat('#', $level).' '.$text."\n\n" : "\n";
        }, $html) ?? $html;

        $html = preg_replace_callback('#<a\b[^>]*href=(["\'])(.*?)\1[^>]*>(.*?)</a>#is', function (array $matches): string {
            $href = trim(html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $text = $this->cleanMarkdownText($matches[3]);

            if ($text === '') {
                return $href;
            }

            if ($href === '' || str_starts_with($href, '#')) {
                return $text;
            }

            return '['.$text.']('.$href.')';
        }, $html) ?? $html;

        $html = preg_replace_callback('#<li\b[^>]*>(.*?)</li>#is', function (array $matches): string {
            $text = $this->cleanMarkdownText($matches[1]);

            return $text !== '' ? "\n- ".$text : "\n";
        }, $html) ?? $html;

        $html = preg_replace('#<(br|hr)\b[^>]*>#i', "\n", $html) ?? $html;
        $html = preg_replace('#</?(p|div|section|article|main|blockquote|ul|ol|table|tbody|thead|tr)\b[^>]*>#i', "\n", $html) ?? $html;
        $html = preg_replace('#</?(td|th)\b[^>]*>#i', ' ', $html) ?? $html;

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t\x{00A0}]+/u", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function cleanMarkdownText(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
