<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Una suscripción de notificaciones push del navegador (Patient o Caregiver), tal
 * como la entrega la Push API del navegador al llamar pushManager.subscribe() —
 * endpoint único por dispositivo/navegador. Varias por actor es normal (celular +
 * computador, por ejemplo).
 */
#[Fillable(['subscriber_type', 'subscriber_id', 'endpoint', 'public_key', 'auth_token', 'content_encoding'])]
class PushSubscription extends Model
{
    public function subscriber(): MorphTo
    {
        return $this->morphTo();
    }
}
