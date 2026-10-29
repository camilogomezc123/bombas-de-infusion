<?php

namespace App\Concerns;

use App\Notifications\PortalAccountInvitation;
use App\Notifications\PortalResetPasswordNotification;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Notifications\Notifiable;

/**
 * Comportamiento compartido entre Patient y Caregiver: son las dos "cuentas" del
 * portal (/portal), cada una con su propio guard de sesión. Agrupa en un solo lugar
 * autenticación, notificaciones y recuperación de contraseña para no duplicar la
 * misma lógica en los dos modelos.
 */
trait PortalAccountAuthenticatable
{
    use Authenticatable;
    use CanResetPassword;
    use Notifiable;

    /**
     * Nombre del guard de sesión de este tipo de cuenta ("patient" | "caregiver") —
     * se usa para construir la URL correcta de restablecimiento de contraseña.
     */
    abstract public function portalGuardName(): string;

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new PortalResetPasswordNotification($token, $this->portalGuardName()));
    }

    /**
     * Genera una contraseña temporal, la guarda hasheada, obliga a cambiarla en el
     * próximo ingreso, y envía la invitación por correo (queda en storage/logs/laravel.log
     * en este entorno de desarrollo — MAIL_MAILER=log, sin correos reales).
     */
    public function sendPortalInvitation(): string
    {
        $temporaryPassword = \Illuminate\Support\Str::password(12);

        $this->forceFill([
            'password' => $temporaryPassword,
            'must_change_password' => true,
        ])->save();

        $this->notify(new PortalAccountInvitation($temporaryPassword, $this->portalGuardName()));

        return $temporaryPassword;
    }
}
