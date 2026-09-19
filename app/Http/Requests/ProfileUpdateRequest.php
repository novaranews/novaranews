<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\ArticleContentRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'public_email' => ['nullable', 'string', 'lowercase', 'email', 'max:255'],
            'show_public_email' => ['nullable', 'boolean'],
            'slug'     => [
                'nullable', 'string', 'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'avatar'   => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'twitter'  => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_]+$/'],
            'linkedin' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^\+?[0-9\s\-\(\)]{6,40}$/'],
            'show_phone' => ['nullable', 'boolean'],
            'whatsapp' => ['nullable', 'string', 'max:40', 'regex:/^\+?[0-9\s\-\(\)]{6,40}$/'],
            'show_whatsapp' => ['nullable', 'boolean'],
            'telegram' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_]{4,100}$/'],
            'show_telegram' => ['nullable', 'boolean'],
            'address' => ['nullable', 'string', 'max:255'],
            'show_address' => ['nullable', 'boolean'],
            'profile_translations' => ['nullable', 'array'],
        ];

        foreach (config('novaranews.locales', ['en']) as $loc) {
            $rules["profile_translations.$loc.title"] = ['nullable', 'string', 'max:100'];
            $rules["profile_translations.$loc.bio"] = ['nullable', 'string', 'max:8000'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! filled($this->input('slug'))) {
                return;
            }
            if (! $this->has('profile_translations')) {
                return;
            }
            $default = config('novaranews.default_locale', 'en');
            $bio = data_get($this->input('profile_translations'), $default.'.bio');
            if (! is_string($bio) || trim($bio) === '') {
                $validator->errors()->add(
                    "profile_translations.$default.bio",
                    __('site.author_bio_required')
                );

                return;
            }
            if (ArticleContentRules::wordCount($bio) < 100) {
                $validator->errors()->add(
                    "profile_translations.$default.bio",
                    __('site.author_bio_min_words', ['min' => 100])
                );
            }
        });
    }
}
