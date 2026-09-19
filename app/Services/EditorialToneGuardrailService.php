<?php

namespace App\Services;

use App\Models\Setting;

class EditorialToneGuardrailService
{
    /**
     * @return array{
     *   risk: 'low'|'medium'|'high',
     *   score: int,
     *   reasons: array<int, string>,
     *   suggestions: array<int, string>
     * }
     */
    public function analyze(string $title, string $description): array
    {
        $title = trim($title);
        $description = trim($description);
        $text = mb_strtolower($title.' '.$description);

        $score = 0;
        $reasons = [];
        $suggestions = [];

        $genericPhrases = $this->genericPhrases();
        foreach ($genericPhrases as $phrase) {
            if ($phrase !== '' && str_contains($text, mb_strtolower((string) $phrase))) {
                $score += 20;
                $reasons[] = 'generic_phrase';
                break;
            }
        }

        $ctaPhrases = $this->ctaPhrases();
        foreach ($ctaPhrases as $phrase) {
            if ($phrase !== '' && str_contains($text, mb_strtolower((string) $phrase))) {
                $score += 25;
                $reasons[] = 'marketing_tone';
                break;
            }
        }

        $titleTokens = preg_split('/\s+/u', mb_strtolower($title), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $descTokens = preg_split('/\s+/u', mb_strtolower($description), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $allTokens = array_values(array_filter(array_merge($titleTokens, $descTokens), fn ($t) => mb_strlen((string) $t) > 2));

        if ($allTokens !== []) {
            $counts = array_count_values($allTokens);
            arsort($counts);
            $topFreq = (int) reset($counts);
            $density = $topFreq / max(1, count($allTokens));
            if ($density >= 0.18) {
                $score += 20;
                $reasons[] = 'keyword_stuffing';
            }
        }

        if (! preg_match('/\d/', $title) && mb_strlen($title) < 38) {
            $score += 10;
            $reasons[] = 'too_generic_title';
        }

        if (mb_strlen($description) > 0 && mb_strlen($description) < 95) {
            $score += 10;
            $reasons[] = 'thin_description';
        }

        if (! preg_match('/\b(what|how|why|when|impact|update|version|model|release|benchmark|price|roadmap)\b/i', $description)) {
            $score += 10;
            $reasons[] = 'low_information_density';
        }

        if ($score >= 40) {
            $suggestions[] = 'specific_entity';
            $suggestions[] = 'value_sentence';
        }
        if ($score >= 60) {
            $suggestions[] = 'remove_marketing';
        }

        $risk = $score >= $this->highRiskThreshold()
            ? 'high'
            : ($score >= $this->mediumRiskThreshold() ? 'medium' : 'low');

        return [
            'risk' => $risk,
            'score' => $score,
            'reasons' => array_values(array_unique($reasons)),
            'suggestions' => array_values(array_unique($suggestions)),
        ];
    }

    public function shouldBlockHighRiskOnPublish(): bool
    {
        return (bool) Setting::get(
            'editorial_guardrail_enabled',
            config('editorial_guardrail.block_high_risk_on_publish', true)
        );
    }

    /**
     * @return array<int, string>
     */
    private function genericPhrases(): array
    {
        $raw = (string) Setting::get('editorial_guardrail_generic_phrases', '');
        if ($raw !== '') {
            return $this->phraseListFromString($raw);
        }

        return config('editorial_guardrail.generic_phrases', []);
    }

    /**
     * @return array<int, string>
     */
    private function ctaPhrases(): array
    {
        $raw = (string) Setting::get('editorial_guardrail_cta_phrases', '');
        if ($raw !== '') {
            return $this->phraseListFromString($raw);
        }

        return config('editorial_guardrail.cta_phrases', []);
    }

    private function mediumRiskThreshold(): int
    {
        return (int) Setting::get(
            'editorial_guardrail_medium_threshold',
            config('editorial_guardrail.medium_risk_threshold', 35)
        );
    }

    private function highRiskThreshold(): int
    {
        $high = (int) Setting::get(
            'editorial_guardrail_high_threshold',
            config('editorial_guardrail.high_risk_threshold', 60)
        );

        return max($high, $this->mediumRiskThreshold() + 1);
    }

    /**
     * @return array<int, string>
     */
    private function phraseListFromString(string $input): array
    {
        return array_values(array_filter(array_map(
            static fn (string $line): string => trim($line),
            preg_split('/\r\n|\r|\n/', $input) ?: []
        )));
    }
}

