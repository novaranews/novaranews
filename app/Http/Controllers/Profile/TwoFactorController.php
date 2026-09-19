<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function start(Request $request, Google2FA $google2fa): RedirectResponse
    {
        $user = $request->user();
        if (! $user->is_admin) {
            abort(403);
        }
        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('profile.edit')->with('status', 'two-factor-already-on');
        }

        $secret = $google2fa->generateSecretKey();
        $request->session()->put('profile.two_factor.pending_secret', encrypt($secret));

        return redirect()->route('profile.edit')->with('status', 'two-factor-scan');
    }

    public function confirm(Request $request, Google2FA $google2fa): RedirectResponse
    {
        $user = $request->user();
        if (! $user->is_admin) {
            abort(403);
        }

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $encrypted = $request->session()->get('profile.two_factor.pending_secret');
        if (! $encrypted) {
            throw ValidationException::withMessages([
                'code' => __('Start setup again to receive a new QR code.'),
            ]);
        }

        $secret = decrypt($encrypted);
        if (! $google2fa->verifyKey($secret, $request->string('code')->trim())) {
            throw ValidationException::withMessages([
                'code' => __('The provided code was invalid.'),
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('profile.two_factor.pending_secret');

        return redirect()->route('profile.edit')->with('status', 'two-factor-enabled');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget('profile.two_factor.pending_secret');

        return redirect()->route('profile.edit');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->is_admin) {
            abort(403);
        }

        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('profile.edit');
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('profile.edit')->with('status', 'two-factor-disabled');
    }
}
