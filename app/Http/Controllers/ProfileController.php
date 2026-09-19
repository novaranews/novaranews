<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ImageOptimizerService;
use Illuminate\Http\RedirectResponse;
use Stevebauman\Purify\Facades\Purify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $twoFactorOtpauth = null;
        $user = $request->user();
        if ($user->is_admin && $request->session()->has('profile.two_factor.pending_secret')) {
            $google2fa = app(Google2FA::class);
            $secret = decrypt($request->session()->get('profile.two_factor.pending_secret'));
            $twoFactorOtpauth = $google2fa->getQRCodeUrl(config('app.name'), (string) $user->email, $secret);
        }

        return view('profile.edit', [
            'user' => $user->load('profileTranslations'),
            'twoFactorOtpauth' => $twoFactorOtpauth,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->has('profile_translations')) {
            $defaultLocale = config('novaranews.default_locale', 'en');
            foreach (config('novaranews.locales', ['en']) as $loc) {
                $payload = (array) $request->input("profile_translations.$loc", []);
                $title = isset($payload['title']) ? trim((string) $payload['title']) : '';
                $bio = isset($payload['bio']) ? trim((string) $payload['bio']) : '';
                if ($bio !== '') {
                    $bio = Purify::clean($bio);
                }
                $request->user()->profileTranslations()->updateOrCreate(
                    ['locale' => $loc],
                    [
                        'title' => $title !== '' ? $title : null,
                        'bio' => $bio !== '' ? $bio : null,
                    ]
                );
            }
            $defaultPayload = (array) $request->input("profile_translations.$defaultLocale", []);
            $dt = isset($defaultPayload['title']) ? trim((string) $defaultPayload['title']) : '';
            $db = isset($defaultPayload['bio']) ? trim((string) $defaultPayload['bio']) : '';
            if ($db !== '') {
                $db = Purify::clean($db);
            }
            $request->user()->title = $dt !== '' ? $dt : null;
            $request->user()->bio = $db !== '' ? $db : null;
        }

        unset($data['profile_translations']);

        // Auto-generate slug from name if left empty
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $old = $request->user()->avatar;
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            $path         = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
            $optimized = app(ImageOptimizerService::class)->optimizeStored($path);
            if (is_string($optimized)) {
                $data['avatar'] = $optimized;
            }
        }

        $request->user()->fill($data);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
