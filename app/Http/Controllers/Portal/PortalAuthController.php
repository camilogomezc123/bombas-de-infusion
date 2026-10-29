<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Caregiver;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PortalAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('portal.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = mb_strtolower(trim($credentials['login'] ?? $credentials['email'] ?? ''));
        if ($login === '') {
            return back()->withErrors(['login' => 'Escribe tu usuario o correo.']);
        }
        $patient = Patient::query()->whereRaw('LOWER(portal_username) = ?', [$login])
            ->orWhereRaw('LOWER(email) = ?', [$login])->first();

        if ($patient && $patient->password && Hash::check($credentials['password'], $patient->password)) {
            $request->session()->regenerate();
            Auth::guard('patient')->login($patient);

            return redirect()->intended(route('portal.home'));
        }

        $caregiver = Caregiver::query()->where('is_active', true)
            ->where(fn ($query) => $query->whereRaw('LOWER(portal_username) = ?', [$login])
                ->orWhereRaw('LOWER(email) = ?', [$login]))->first();

        if ($caregiver && Hash::check($credentials['password'], $caregiver->password)) {
            $request->session()->regenerate();
            Auth::guard('caregiver')->login($caregiver);

            return redirect()->intended(route('portal.home'));
        }

        return back()->withErrors(['login' => 'El usuario, correo o contraseña no son correctos.'])->onlyInput('login');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('patient')->logout();
        Auth::guard('caregiver')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
