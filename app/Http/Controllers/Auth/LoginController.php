<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, (bool) $request->boolean('remember'))) {
            $user = Auth::user();
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Akun Anda dinonaktifkan.']);
            }
            // Block kalau tenant-nya suspended (superadmin bypass — gak terikat tenant subscription)
            if ($user->tenant_id && $user->role?->name !== 'superadmin') {
                $tenant = $user->tenant;
                if ($tenant && !$tenant->is_active) {
                    Auth::logout();
                    return back()->withErrors(['email' => 'Tenant Anda sedang nonaktif/suspended. Hubungi admin platform untuk reaktivasi.']);
                }
            }
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Kombinasi email & password tidak cocok.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
