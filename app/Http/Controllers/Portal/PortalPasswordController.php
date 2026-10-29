<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Caregiver;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PortalPasswordController extends Controller
{
    public function showForgot(): View
    {
        return view('portal.auth.forgot-password');
    }

    /**
     * Igual que el login del portal: primero intenta como paciente, luego como
     * cuidador. Responde siempre el mismo mensaje exista o no la cuenta, para no
     * revelar si un correo está registrado.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        if (Patient::query()->where('email', $data['email'])->exists()) {
            Password::broker('patients')->sendResetLink($data);
        } elseif (Caregiver::query()->where('email', $data['email'])->where('is_active', true)->exists()) {
            Password::broker('caregivers')->sendResetLink($data);
        }

        return back()->with('status', 'Si el correo está registrado, te enviamos instrucciones para restablecer tu contraseña.');
    }

    public function showReset(Request $request): View
    {
        return view('portal.auth.reset-password', [
            'token' => $request->query('token'),
            'guard' => $request->query('guard', 'patient'),
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'guard' => ['required', 'in:patient,caregiver'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $broker = $data['guard'] === 'caregiver' ? 'caregivers' : 'patients';

        // El broker solo debe recibir token/email/password — "guard" es un detalle
        // nuestro para elegir el broker correcto, no una credencial del modelo.
        $status = Password::broker($broker)->reset(
            Arr::except($data, ['guard']),
            function ($user, string $password): void {
                $user->forceFill(['password' => $password, 'must_change_password' => false])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'El enlace no es válido o ya expiró.']);
        }

        return redirect()->route('portal.login')->with('status', 'Tu contraseña quedó actualizada. Ya puedes ingresar.');
    }

    public function showForceChange(): View
    {
        return view('portal.auth.force-change-password');
    }

    public function forceChange(Request $request): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);

        $actor = Auth::guard('patient')->user() ?? Auth::guard('caregiver')->user();
        abort_unless($actor, 403);

        $actor->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();

        return redirect()->route('portal.home')->with('status', 'Contraseña actualizada.');
    }
}
