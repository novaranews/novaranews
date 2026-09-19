<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.two_factor.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, Google2FA $google2fa): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $id = $request->session()->get('login.two_factor.id');
        $user = User::query()->find($id);

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget(['login.two_factor.id', 'login.two_factor.remember']);

            return redirect()->route('login')
                ->withErrors(['code' => __('This challenge is no longer valid. Please sign in again.')]);
        }

        if (! $google2fa->verifyKey($user->two_factor_secret, $request->string('code')->trim())) {
            throw ValidationException::withMessages([
                'code' => __('The provided code was invalid.'),
            ]);
        }

        $remember = (bool) $request->session()->pull('login.two_factor.remember', false);
        $request->session()->forget('login.two_factor.id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
