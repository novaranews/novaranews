<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $rows = [
            [
                'key' => 'editorial_guardrail_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'site',
                'label' => 'Editorial Guardrail Enabled',
                'description' => 'Block publish action for high-risk robotic tone',
            ],
            [
                'key' => 'editorial_guardrail_medium_threshold',
                'value' => '35',
                'type' => 'integer',
                'group' => 'site',
                'label' => 'Editorial Guardrail Medium Threshold',
                'description' => 'Minimum score treated as medium risk',
            ],
            [
                'key' => 'editorial_guardrail_high_threshold',
                'value' => '60',
                'type' => 'integer',
                'group' => 'site',
                'label' => 'Editorial Guardrail High Threshold',
                'description' => 'Minimum score treated as high risk',
            ],
            [
                'key' => 'editorial_guardrail_generic_phrases',
                'value' => implode("\n", config('editorial_guardrail.generic_phrases', [])),
                'type' => 'string',
                'group' => 'site',
                'label' => 'Editorial Guardrail Generic Phrases',
                'description' => 'New-line separated phrases considered generic/robotic',
            ],
            [
                'key' => 'editorial_guardrail_cta_phrases',
                'value' => implode("\n", config('editorial_guardrail.cta_phrases', [])),
                'type' => 'string',
                'group' => 'site',
                'label' => 'Editorial Guardrail CTA Phrases',
                'description' => 'New-line separated CTA phrases considered overly promotional',
            ],
        ];

        foreach ($rows as $row) {
            DB::table('settings')->updateOrInsert(
                ['key' => $row['key']],
                $row + ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->whereIn('key', [
            'editorial_guardrail_enabled',
            'editorial_guardrail_medium_threshold',
            'editorial_guardrail_high_threshold',
            'editorial_guardrail_generic_phrases',
            'editorial_guardrail_cta_phrases',
        ])->delete();
    }
};

