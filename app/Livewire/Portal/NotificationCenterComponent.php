<?php

namespace App\Livewire\Portal;

use App\Models\Caregiver;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Campanita de notificaciones del portal — junta en un solo lugar los avisos que
 * antes solo se veían si la persona entraba módulo por módulo a revisar (empieza con
 * "tu equipo respondió tu solicitud"; queda listo para sumar más disparadores).
 * Patient/Caregiver ya son Notifiable (vía PortalAccountAuthenticatable), así que
 * reutiliza directamente las tablas estándar de notificaciones de Laravel.
 */
class NotificationCenterComponent extends Component
{
    public function markAllAsRead(): void
    {
        $actor = $this->actor();
        abort_unless($actor, 403);

        $actor->unreadNotifications->markAsRead();
    }

    private function actor(): Patient|Caregiver|null
    {
        return Auth::guard('patient')->user() ?? Auth::guard('caregiver')->user();
    }

    public function render()
    {
        $actor = $this->actor();

        return view('livewire.portal.notification-center-component', [
            'notifications' => $actor ? $actor->notifications()->latest()->take(15)->get() : collect(),
            'unreadCount' => $actor ? $actor->unreadNotifications()->count() : 0,
        ]);
    }
}
