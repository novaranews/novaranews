<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Services\SiteSeoService;
use App\Support\RecaptchaVerifier;
use App\Support\StaticPageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(string $locale, SiteSeoService $seoService): View
    {
        // Only the DB slug URL should work. If the current URL doesn't match the
        // locale's canonical slug (e.g. /es/contact vs /es/iletisim), return 404.
        $canonical = rtrim(page_url('contact', $locale), '/');
        if (rtrim(request()->url(), '/') !== $canonical) {
            abort(404);
        }

        abort_unless(StaticPageContent::isActive('contact'), 404);
        $content = StaticPageContent::resolve('contact', $locale);

        return view('site.pages.contact', [
            'localeSwitchUrls' => $this->localeSwitchUrls('contact'),
            'seo' => $seoService->staticPage($content),
            'pageContent' => $content['html'],
            'pageHeading' => $content['heading'],
        ]);
    }

    public function store(Request $request, string $locale, RecaptchaVerifier $recaptcha): RedirectResponse
    {
        abort_unless(StaticPageContent::isActive('contact'), 404);

        if (! in_array($locale, config('novaranews.locales', ['en']), true)) {
            abort(404);
        }

        if (! $recaptcha->verify($request, 'contact')) {
            return back()->withErrors(['captcha' => __('site.contact_captcha_failed')])->withInput();
        }

        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        // Always save to database so it shows in admin panel
        ContactMessage::create([
            'name'    => $validated['name'],
            'email'   => $validated['email'],
            'locale'  => $locale,
            'subject' => $validated['subject'] ?? null,
            'message' => $validated['message'],
            'ip'      => $request->ip(),
        ]);

        // Also send email notification if configured
        $to = Setting::site('contact_mail_to', config('novaranews.contact_mail_to'));
        if ($to) {
            $subject = '['.config('app.name').'] '.($validated['subject'] ?? __('site.contact_mail_default_subject', [], $locale)).' ('.$locale.')';
            $body    = __('site.contact_mail_body_name', [], $locale).": {$validated['name']}\n"
                .__('site.contact_mail_body_email', [], $locale).": {$validated['email']}\n"
                .__('site.contact_mail_body_locale', [], $locale).": {$locale}\n\n"
                .$validated['message'];

            try {
                Mail::raw($body, function ($msg) use ($to, $subject, $validated) {
                    $msg->to($to)
                        ->replyTo($validated['email'], $validated['name'])
                        ->subject($subject);
                });
            } catch (\Throwable $e) {
                Log::warning('Contact mail failed: '.$e->getMessage());
            }
        }

        return redirect()
            ->to(page_url('contact', $locale))
            ->with('contact_status', 'sent');
    }

    private function localeSwitchUrls(string $pageKey): array
    {
        return collect(config('novaranews.locales'))
            ->mapWithKeys(fn (string $l) => [$l => page_url($pageKey, $l)])
            ->all();
    }
}
